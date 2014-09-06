<?php

class Crimsonwing_Focum_Model_System_Config_Source_Mode
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'test', 'label' => Mage::helper('focum')->__('Test Mode')),
            array('value' => 'live', 'label' => Mage::helper('focum')->__('Production Mode')),
        );
    }
}