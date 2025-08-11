<?php

class MageShop_StoreSchedule_Model_Observer
{
    // Método chamado quando o evento configurado no config.xml é disparado
    public function checkShippingMethods(Varien_Event_Observer $observer)
    {
        // Instancia o helper do módulo para acessar métodos utilitários (ex: isStoreOpen)
        /** @var MageShop_StoreSchedule_Helper_Data $helper */
        $helper = Mage::helper('storeschedule');

        // Verifica se o módulo está ativado no painel do Magento
        if (!$helper->isScheduleModuleEnabled()) {
            // Se não estiver, registra no log e encerra a função
            Mage::log('Módulo StoreSchedule desativado - ignorando lógica.', null, 'storeschedule.log');
            return;
        }

        // Mage::log('Observer disparado: checkShippingMethods', null, 'storeschedule.log');

        // Obtém a sessão do checkout (onde ficam as informações do carrinho)
        /** @var Mage_Checkout_Model_Session $session */
        $session = Mage::getSingleton('checkout/session');
        // Pega o quote atual, que representa o carrinho de compras
        $quote = $session->getQuote();
        // Acessa o endereço de entrega (shipping address) do carrinho
        $address = $quote->getShippingAddress();

        // Verifica se a loja está aberta com base na data e horários configurados
        $storeIsOpen = $helper->isStoreOpen();
        // Verifica se o delivery está disponível
        $deliveryAvailable = $helper->isDeliveryAvailable();

        // Mage::log("Loja aberta? " . ($storeIsOpen ? 'Sim' : 'Não'), null, 'storeschedule.log');
        // Mage::log("Delivery disponível? " . ($deliveryAvailable ? 'Sim' : 'Não'), null, 'storeschedule.log');

        // Obtém os métodos permitidos a partir da configuração do admin
        $storeOpenMethodsConfig = Mage::getStoreConfig('storeschedule/general/store_open_methods');
        $deliveryMethodsConfig  = Mage::getStoreConfig('storeschedule/general/delivery_available_methods');

        // Transforma as strings CSV em arrays
        $storeOpenMethods = array_filter(explode(',', $storeOpenMethodsConfig));
        $deliveryMethods  = array_filter(explode(',', $deliveryMethodsConfig));

        // Inicializa array onde serão adicionados os métodos de envio permitidos
        $allowedMethods = [];

        $address->collectShippingRates()->save();
        $rates = $address->getAllShippingRates();

       // Se a loja estiver aberta, permite pelo menos o "Retirar na loja" (Flat Rate)
       if ($storeIsOpen) {
            foreach ($rates as $rate) {
                $code = $rate->getCode(); // exemplo: flatrate_flatrate
                $carrier = explode('_', $code)[0]; // extrai o carrier: flatrate

                if (in_array($carrier, $storeOpenMethods)) {
                    $allowedMethods[] = $code;
                }
            }

            if ($deliveryAvailable) {
                foreach ($rates as $rate) {
                    $code = $rate->getCode();
                    $carrier = explode('_', $code)[0];

                    if (in_array($carrier, $deliveryMethods)) {
                        $allowedMethods[] = $code;
                    }
                }
            }

            $allowedMethods = array_unique($allowedMethods); // remove duplicatas
        }

        // Mage::log("Métodos permitidos: " . implode(', ', $allowedMethods), null, 'storeschedule.log');

        // Se ainda não tiver carregado os métodos (por alguma falha), carrega agora
        if (empty($rates)) {
             // Atualiza as opções de envio disponíveis para esse endereço
            $address->collectShippingRates()->save();
            // Recupera todos os métodos de envio disponíveis após recalcular as opções
            $rates = $address->getAllShippingRates();
        }

        // Percorre cada método de envio e verifica se ele está na lista dos permitidos
        foreach ($rates as $rate) {
            // Se não estiver na lista de permitidos, remove esse método do renderizador
            if (!in_array($rate->getCode(), $allowedMethods)) {
                $rate->isDeleted(true); // "Marca" o método para não ser exibido no frontend
                // Mage::log("Removido: " . $rate->getCode(), null, 'storeschedule.log');
            } else {
                // Mage::log("Mantido: " . $rate->getCode(), null, 'storeschedule.log');
            }
        }

        // Agora, verifica se o método de envio atualmente selecionado ainda é válido
        $selectedMethod = $address->getShippingMethod();

        // Se o método selecionado pelo cliente não está mais entre os permitidos...
        if (!in_array($selectedMethod, $allowedMethods)) {
            // Pega o primeiro método permitido como novo método de envio
            $newMethod = reset($allowedMethods); // reset() retorna o primeiro elemento do array
            if ($newMethod) {
                // Define o novo método de envio
                $address->setShippingMethod($newMethod);
                // Mage::log("Alterando para: " . $newMethod, null, 'storeschedule.log');
            } else {
                // Se não houver nenhum método permitido, limpa o campo
                $address->setShippingMethod(null);
                // Mage::log("Nenhum método aplicável disponível.", null, 'storeschedule.log');
            }
        }

        // Salva as alterações feitas no endereço de entrega (shipping)
        $address->save();
    }
}