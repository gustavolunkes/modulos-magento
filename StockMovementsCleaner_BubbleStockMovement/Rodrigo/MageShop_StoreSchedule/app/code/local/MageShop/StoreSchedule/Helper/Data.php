<?php
class MageShop_StoreSchedule_Helper_Data extends Mage_Core_Helper_Abstract
{
    // Método que verifica se o módulo StoreSchedule está ativo
    public function isScheduleModuleEnabled()
    {
        // Recupera o valor booleano da configuração 'storeschedule/general/enabled'
        // getStoreConfigFlag retorna true se estiver ativado (ex: '1'), false se não
        Mage::log("Módulo ativo", null, "storeschedule.log");
        return Mage::getStoreConfigFlag('storeschedule/general/enabled');
    }
    /**
     * Verifica se a loja está aberta no momento
     */
    public function isStoreOpen()
    {
        // Pega o dia da semana atual em inglês minúsculo: 'monday', 'tuesday', etc.
        // date('l') retorna o dia da semana com a primeira letra maiúscula, por isso strtolower
        $dayOfWeek = strtolower(date('l', Mage::getModel('core/date')->timestamp()));
        // Recupera a configuração armazenada para o dia da semana atual
        // Essa configuração é um JSON com os horários para loja e delivery para esse dia
        $scheduleJson = Mage::getStoreConfig("storeschedule/schedule/{$dayOfWeek}");

        // Se não existir configuração para esse dia, retorna falso (loja fechada)
        if (!$scheduleJson) {
            // Mage::log("Loja fora do horário de funcionamento (fechada)", null, 'store_schedule.log', true);
            return false;
        }

        // Decodifica o JSON em um array associativo
        // Esse JSON deve conter as chaves 'abre1' e 'fecha1', com os horários no formato "HH:MM"
        $schedule = json_decode($scheduleJson, true);
        // Se não conseguir decodificar ou o resultado não for array, considera loja fechada
        if (!is_array($schedule)) {
            Mage::log("Não foi possível decodificar o array.", null, 'store_schedule.log', true);
            return false;
        }

        // Log no arquivo store_schedule.log o array decodificado para fins de depuração
        // Isso ajuda a verificar se os dados estão corretos (ex: ["abre1" => "09:30", "fecha1" => "01:00"])
        // Mage::log("Array decode: " . print_r($schedule, true), null, 'store_schedule.log');

        // Extrai os horários de abertura e fechamento da loja (abre1 e fecha1)
        // Se algum deles não estiver definido no JSON, retorna false (loja fechada)
        $openStr  = isset($schedule['abre1']) ? $schedule['abre1'] : false;
        $closeStr = isset($schedule['fecha1']) ? $schedule['fecha1'] : false;

        // Se qualquer um dos horários estiver ausente ou vazio, considera loja fechada
        if (!$openStr || !$closeStr) {
            // Mage::log("Hora vazia ou fora do horário de atendimento.", null, 'store_schedule.log', true);
            return false;
        }
      
        // Obtém o horário atual como timestamp (formato Unix)
        // Usa o core/date do Magento para respeitar o timezone configurado na loja
        $now = Mage::getModel('core/date')->timestamp();

        // Define a data de hoje no formato 'Y-m-d' (ex: 2025-07-03)
        $today = date('Y-m-d', $now);
        // Define a data de amanhã, usada caso o horário de fechamento seja após a meia-noite
        $tomorrow = date('Y-m-d', strtotime('+1 day', $now));

        // Concatena a data de hoje com o horário de abertura e converte para timestamp
        // Exemplo: "2025-07-03 09:30" => timestamp de abertura
        $openTimestamp  = strtotime("$today $openStr");
        // Concatena a data de hoje com o horário de fechamento e converte para timestamp
        // Exemplo: "2025-07-03 23:00" => timestamp de fechamento (ainda no mesmo dia)
        $closeTimestamp = strtotime("$today $closeStr");

        // Se o horário de fechamento for menor ou igual ao de abertura
        if ($closeTimestamp <= $openTimestamp) {
            // Loja fecha no dia seguinte
            $closeTimestamp = strtotime("$tomorrow $closeStr");

            // Se o horário atual for menor que o de abertura,
            // e for entre 00:00 e 05:00, é provavelmente a madrugada do dia seguinte
            if ($now < $openTimestamp && date('H', $now) < 5) {
                $now = strtotime("$tomorrow " . date('H:i', $now));
            }
        }

        //Mage::log("AGORA: " . date('Y-m-d H:i:s', $now), null, 'store_schedule.log', true);
        //Mage::log("ABRE: " . date('Y-m-d H:i:s', $openTimestamp), null, 'store_schedule.log', true);
        //Mage::log("FECHA: " . date('Y-m-d H:i:s', $closeTimestamp), null, 'store_schedule.log', true);

        // Retorna true se o horário atual estiver dentro do intervalo entre abertura e fechamento
        // Caso contrário, retorna false
        return ($now >= $openTimestamp && $now <= $closeTimestamp);
    }

    /**
     * Verifica se o delivery está disponível no momento
     */
    public function isDeliveryAvailable()
    {
        // Primeiro, verifica se a loja está aberta
        // Se a loja estiver fechada, o delivery também deve estar indisponível
        if (!$this->isStoreOpen()) {
            //Mage::log("Loja fechada", null, 'store_schedule.log', true);
            return false;
        }
      
      	// Obtém o horário atual com timezone configurado no Magento
   		$now = Mage::getModel('core/date')->timestamp();

        // Obtém o dia da semana atual em inglês minúsculo: 'monday', 'tuesday', etc.
        // A função date('l') retorna com a primeira letra maiúscula, por isso strtolower para padronizar
        $dayOfWeek = strtolower(date('l', Mage::getModel('core/date')->timestamp()));

        // Recupera a configuração JSON de horários para o dia atual da semana
        // A configuração deve estar no caminho: storeschedule/schedule/[dia_da_semana]
        $scheduleJson = Mage::getStoreConfig("storeschedule/schedule/{$dayOfWeek}");

        // Se não houver configuração para o dia atual, considera delivery indisponível
        if (!$scheduleJson) {
            Mage::log("Sem config para delivery", null, 'store_schedule.log', true);
            return false;
        }

        // Decodifica o JSON para um array associativo
        // Deve conter as chaves 'abre2' e 'fecha2' com os horários de funcionamento do delivery
        $schedule = json_decode($scheduleJson, true);
        // Se a decodificação falhar ou o resultado não for array, considera delivery indisponível
        if (!is_array($schedule)) {
            Mage::log("Decodificação falhou", null, 'store_schedule.log', true);
            return false;
        }

        // Extrai os horários de abertura e fechamento do delivery (abre2 e fecha2)
        // Se alguma dessas chaves não existir ou estiver vazia, delivery indisponível
        $openStr  = isset($schedule['abre2']) ? $schedule['abre2'] : false;
        $closeStr = isset($schedule['fecha2']) ? $schedule['fecha2'] : false;

        if (!$openStr || !$closeStr) {
            Mage::log("Chave de delivery vazia ou inexistente", null, 'store_schedule.log', true);
            return false;
        }

        // Define a data de hoje e de amanhã no formato 'Y-m-d'
        // Isso será usado para construir os timestamps de comparação
		$today = date('Y-m-d', $now);
    	$tomorrow = date('Y-m-d', strtotime('+1 day', $now));

        // (Opcional para testes) Força um horário simulado
        // $now = strtotime(date('Y-m-d', strtotime('+1 day')) . ' 00:40');

        // Concatena a data de hoje com o horário de abertura e fechamento, e converte para timestamp
        $openTimestamp  = strtotime("$today $openStr");
        $closeTimestamp = strtotime("$today $closeStr");

        // Se o horário de fechamento for menor ou igual ao de abertura,
        // significa que o delivery fecha no dia seguinte (ex: abre às 19:00 e fecha às 02:00 da manhã seguinte)// Se o fechamento for menor ou igual à abertura, considera que o fechamento é no dia seguinte
        if ($closeTimestamp <= $openTimestamp) {
            // Atualiza o fechamento para o horário do dia seguinte
            $closeTimestamp = strtotime("$tomorrow $closeStr");

            // Ajusta $now se for madrugada (entre 00:00 e 05:00)
            $currentHour = (int)date('H', $now);
            if ($now < $openTimestamp && $currentHour < 5) {
                $now = strtotime("$tomorrow " . date('H:i', $now));
            }
        }

        //Mage::log("DELIVERY - AGORA: " . date('Y-m-d H:i:s', $now), null, 'store_schedule.log', true);
        //Mage::log("DELIVERY - ABRE: " . date('Y-m-d H:i:s', $openTimestamp), null, 'store_schedule.log', true);
        //Mage::log("DELIVERY - FECHA: " . date('Y-m-d H:i:s', $closeTimestamp), null, 'store_schedule.log', true);


        // Verifica se horário atual está dentro do intervalo de delivery
        if ($now >= $openTimestamp && $now <= $closeTimestamp) {
            // Mage::log("Horário dentro do intervalo de delivery, delivery disponível", null, 'store_schedule.log', true);
            return true; // Delivery está disponível
        }
      
        // Caso o horário atual esteja fora do intervalo, delivery indisponível
        return false;
    }

}
