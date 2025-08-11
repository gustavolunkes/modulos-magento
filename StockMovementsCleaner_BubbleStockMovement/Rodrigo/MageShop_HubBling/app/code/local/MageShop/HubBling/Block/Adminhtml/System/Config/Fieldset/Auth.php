<?php

class MageShop_HubBling_Block_Adminhtml_System_Config_Fieldset_Auth extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    private $helper;

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $html = $element->getElementHtml();
        $urlCallback = Mage::getUrl("hubbling/authorization/index", array('_secure' => true));

        $html .= '<div style="margin-top: 10px; padding: 10px; border-left: 4px solid #0073aa; background: #f1f1f1; border-radius: 5px; font-size: 14px;">';
        $html .= '<strong>URL de Callback:</strong><br>';
        $html .= '<a href="' . $urlCallback . '" target="_blank" style="color: #0073aa; text-decoration: none; font-weight: bold;">' . $urlCallback . '</a>';
        $html .= '<p style="margin-top: 5px; font-size: 12px; color: #666;">Copie e cole no seu APP.</p>';
        $html .= '</div> <br>';

        if ($this->oauth()) {
            $html .= $this->_getAddRowButtonHtml("Autenticação API");
        }

        return $html;
    }


    private function oauth()
    {
        return $this->_helper()->getConfigData('client_secret_key') && $this->_helper()->getConfigData('client_public_key');
    }

    protected function _getAddRowButtonHtml($title)
    {

        $url = $this->_helper()->getOAuth();
        $buttonHtml = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setLabel($this->__($title))
            ->setOnClick("window.location.href='" . $url . "'")
            ->toHtml();

        return $buttonHtml;
    }

    private function _helper()
    {
        if (!$this->helper) {
            return $this->helper = Mage::helper('hubbling');
        }

        return $this->helper;
    }
}
