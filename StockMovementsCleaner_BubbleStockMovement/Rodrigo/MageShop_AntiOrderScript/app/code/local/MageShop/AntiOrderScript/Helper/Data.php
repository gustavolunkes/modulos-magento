<?php
class MageShop_AntiOrderScript_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function isEnabled()
    {
        return Mage::getStoreConfig('customer/antiorderscript_validation/enable_module');
    }

    public function isLogEnabled()
    {
        return Mage::getStoreConfig('customer/antiorderscript_validation/enable_log');
    }
}
