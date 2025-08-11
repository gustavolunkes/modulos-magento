<?php
class MageShop_HubBling_Model_Resource_SyncProduct
extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Resource initialization
     *
     * @see Mage_Core_Model_Resource_Abstract::_construct()
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('hubbling/syncProduct', 'id');
    }
}
