<?php
class MageShop_HubBling_Model_Resource_SyncProduct_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Resource initialization
     *
     * @see Mage_Core_Model_Resource_Db_Collection_Abstract::_construct()
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('hubbling/syncProduct');
    }
}
