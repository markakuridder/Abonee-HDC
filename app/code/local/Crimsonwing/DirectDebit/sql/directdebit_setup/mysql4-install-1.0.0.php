<?php
$this->startSetup();
$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
$this->getConnection()->addColumn($this->getTable('sales_flat_quote_payment'), 'customer_bankaccount', "char(10)");
$this->getConnection()->addColumn($this->getTable('sales_flat_order_payment'), 'customer_bankaccount', "char(10)");
$setup->addAttribute('order_payment', 'customer_bankaccount', array(
    'label' => 'Customer Bank Account Number',
    'visible' => true,
    'required' => false,
    'position' => 1,
));
$setup->addAttribute('quote_payment', 'customer_bankaccount', array(
    'label' => 'Customer Bank Account Number',
    'visible' => true,
    'required' => false,
    'position' => 1,
));
$this->endSetup();