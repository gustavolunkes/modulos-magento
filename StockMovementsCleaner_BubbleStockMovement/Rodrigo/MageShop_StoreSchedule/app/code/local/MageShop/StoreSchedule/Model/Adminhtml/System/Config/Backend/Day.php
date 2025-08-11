<?php
class MageShop_StoreSchedule_Model_Adminhtml_System_Config_Backend_Day extends Mage_Core_Model_Config_Data
{
    // Este método é automaticamente chamado pelo Magento antes de salvar a configuração no banco
    protected function _beforeSave()
    {
        // Obtém o valor atual da configuração que será salva
        $value = $this->getValue();

        // Verifica se o valor NÃO é um array (caso seja uma string JSON vinda do formulário)
        if (!is_array($value)) {
             // Tenta decodificar o valor como JSON para transformá-lo em um array
            $value = json_decode($value, true);
            // Se ainda assim não for um array válido (por exemplo, se o JSON for inválido ou vazio)
            if (!is_array($value)) {
                // Define como array vazio para evitar erros
                $value = [];
            }
        }

        // Define as chaves obrigatórias que esperamos existir no array (referentes aos horários)
        $keys = ['abre1', 'fecha1', 'abre2', 'fecha2'];
        // Para cada uma dessas chaves (horário de abertura e fechamento da loja e do delivery)
        foreach ($keys as $key) {
            // Se a chave não estiver presente no array, adiciona ela com valor vazio
            // Isso garante que todas as 4 chaves existam, mesmo que não tenham sido preenchidas no formulário
            if (!isset($value[$key])) {
                $value[$key] = '';
            }
        }

        // Converte o array de volta para uma string JSON para salvar no banco de dados
        $this->setValue(json_encode($value));
        // Chama o método pai (_beforeSave da classe base Mage_Core_Model_Config_Data) para garantir que o comportamento padrão do Magento também ocorra
        return parent::_beforeSave();
    }
}
