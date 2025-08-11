<?php

class MageShop_UrlRedirect_Model_System_Config_Source_Redirecttype
{
    public function toOptionArray()
    {
        return [
            ['value' => 301, 'label' => '301 - Permanente'],
            ['value' => 302, 'label' => '302 - Temporário'],
        ];
    }
}
