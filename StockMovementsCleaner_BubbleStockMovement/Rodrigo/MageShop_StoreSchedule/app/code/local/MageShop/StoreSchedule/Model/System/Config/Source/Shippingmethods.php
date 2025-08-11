<?php
class MageShop_StoreSchedule_Model_System_Config_Source_Shippingmethods
{
    public function toOptionArray()
    {
        $methods = array();

        $carriers = Mage::getSingleton('shipping/config')->getAllCarriers();

        foreach ($carriers as $code => $carrier) {
            if ($carrier->isActive()) {
                $title = Mage::getStoreConfig('carriers/' . $code . '/title');
                $methods[] = array(
                    'value' => $code,
                    'label' => $title . ' (' . $code . ')'
                );
            }
        }

        return $methods;
    }
}