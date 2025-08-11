<?php

class MageShop_SetMailToCancel_Model_System_Config_Source_Email_Copymethod
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'bcc', 'label' => 'Bcc'),
            array('value' => 'copy', 'label' => 'Email Separado'),
        );
    }
}
