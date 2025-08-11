<?php
class MageShop_CustomerValidation_Model_Customer extends Mage_Customer_Model_Customer
{
    /**
     * Sobrescreve o método validate para impedir números em nome/sobrenome
     */
    public function validate()
    {
        if (!Mage::getStoreConfigFlag('customer/create_account/enable_customer_validation')){
            return;
        }

        $errors = array();

        // Nome: apenas letras e espaços
        if (!Zend_Validate::is($this->getFirstname(), 'Regex', array('/^[A-Za-zÀ-ÿ\s]+$/'))) {
            $errors[] = Mage::helper('customer')->__('O nome deve conter apenas letras.');
        }

        // Sobrenome: apenas letras e espaços
        if (!Zend_Validate::is($this->getLastname(), 'Regex', array('/^[A-Za-zÀ-ÿ\s]+$/'))) {
            $errors[] = Mage::helper('customer')->__('O sobrenome deve conter apenas letras.');
        }

        // Chama a validação original do core para manter os outros checks
        $coreValidation = parent::validate();
        if ($coreValidation !== true) {
            $errors = array_merge($errors, $coreValidation);
        }

        return empty($errors) ? true : $errors;
    }
}
