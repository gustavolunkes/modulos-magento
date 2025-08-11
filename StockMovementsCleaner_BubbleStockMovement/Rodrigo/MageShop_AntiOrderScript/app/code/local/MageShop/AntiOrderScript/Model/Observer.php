<?php
class MageShop_AntiOrderScript_Model_Observer
{
    public function validarCadastro(Varien_Event_Observer $observer)
    {
        /** @var MageShop_AntiOrderScript_Helper_Data $helper */
        $helper = Mage::helper('mageshop_antiorderscript');

        if (!$helper->isEnabled()) {
            return $this;
        }

            $customer = $observer->getCustomer();
            $firstname = $customer->getFirstname();
            $lastname = $customer->getLastname();
            $email = $customer->getEmail();
        

            if ($this->contemScript($firstname) || $this->contemScript($lastname) || $this->contemScript($email)) {
                
                if ($helper->isLogEnabled()) {                    
                    Mage::log(
                        "==================== BLOQUEIO DE CADASTRO ====================\n" .
                        "Data/Hora: " . date('Y-m-d H:i:s') . "\n" .
                        "IP do usuário: " . $_SERVER['REMOTE_ADDR'] . "\n" .
                        "User-Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\n" .
                        "Nome: $firstname\n" .
                        "Sobrenome: $lastname\n" .
                        "Email: $email\n" .
                        "Motivo: Campo com possível código/script detectado\n" .
                        "Payload POST:\n" . print_r($_POST, true) . "\n" .
                        "==============================================================\n",
                        null,
                        'antiorderscript.log',
                        true
                    );
                }

                Mage::throwException('Dados inválidos no cadastro. Ação bloqueada por segurança.');
            }


        return $this;
    }

    public function validarPedido(Varien_Event_Observer $observer)
    {
        /** @var MageShop_AntiOrderScript_Helper_Data $helper */
        $helper = Mage::helper('mageshop_antiorderscript');

        if (!$helper->isEnabled()) {
            return $this;
        }

        $order = $observer->getOrder();
        $firstname = $order->getFirstname();
        $lastname = $order->getLastname();
        $email = $order->getEmail();

        if (!$firstname && isset($_POST['billing']['firstname'])) {
            $firstname = $_POST['billing']['firstname'];
        }
        if (!$lastname && isset($_POST['billing']['lastname'])) {
            $lastname = $_POST['billing']['lastname'];
        }
        if (!$email && isset($_POST['billing']['email'])) {
            $email = $_POST['billing']['email'];
        }
        
        $billing = $order->getBillingAddress();
        
        $street = $billing->getStreet(); // Retorna array com até 4 linhas
        $address            = isset($street[0]) ? $street[0] : '';
        $numberAddress      = isset($street[1]) ? $street[1] : '';
        $additionalAddress  = isset($street[2]) ? $street[2] : '';
        $neighborhood       = isset($street[3]) ? $street[3] : '';
        $city = $billing->getCity();
        
        if (
            $this->contemScript($firstname) ||
            $this->contemScript($lastname) ||
            $this->contemScript($email) ||
            $this->contemScript($address) ||
            $this->contemScript($numberAddress) ||
            $this->contemScript($additionalAddress) ||
            $this->contemScript($neighborhood) ||
            $this->contemScript($city)
            ) {
                
            if ($helper->isLogEnabled()) {
                Mage::log(
                    "==================== BLOQUEIO DE CADASTRO CHECKOUT ====================\n" .
                    "Data/Hora: " . date('Y-m-d H:i:s') . "\n" .
                    "IP do usuário: " . $_SERVER['REMOTE_ADDR'] . "\n" .
                    "User-Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\n" .
                    "Nome: $firstname\n" .
                    "Sobrenome: $lastname\n" .
                    "Email: $email\n" .
                    "Endereço: $address\n" .
                    "Número: $numberAddress\n" .
                    "Complemento: $additionalAddress\n" .
                    "Bairro: $neighborhood\n" .
                    "Cidade: $city\n" .
                    "Motivo: Campo com possível código/script detectado\n" .
                    "Payload POST:\n" . print_r($_POST, true) . "\n" .
                    "==============================================================\n",
                    null,
                    'antiorderscript.log',
                    true
                );
            }

            Mage::throwException('Dados inválidos no pedido. Ação bloqueada por segurança.');
        }


        return $this;
    }

    protected function contemScript($valor)
    {
        return preg_match('/<(script|.*on\w+)=?|javascript:/i', $valor);
    }
}