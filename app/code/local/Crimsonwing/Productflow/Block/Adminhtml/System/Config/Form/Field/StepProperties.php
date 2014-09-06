<?php

/** Derived from Mage_CatalogInventory_Block_Adminhtml_Form_Field_Minsaleqty */
class Crimsonwing_Productflow_Block_Adminhtml_System_Config_Form_Field_StepProperties extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $_renderer = null;

    /**
     * Prepare to render
     */
    protected function _prepareToRender()
    {
        $this->addColumn('step', array(
            'label' => Mage::helper('productflow')->__('Step'),
            'style' => 'width:70px',
        ));
        $this->addColumn('min_qty', array(
            'label' => Mage::helper('productflow')->__('Min Qty'),
            'style' => 'width:70px',
        ));
        $this->addColumn('max_qty', array(
            'label' => Mage::helper('productflow')->__('Max Qty'),
            'style' => 'width:70px',
        ));
        $this->addColumn('clear_cart', array(
            'label' => Mage::helper('productflow')->__('Clear Cart'),
            'style' => 'width:70px',
        ));
        $this->_addAfter = false;
    }

}
