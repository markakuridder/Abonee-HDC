<?php
// For ereader when accessories are bought along with the subscription
// then the user has to pay using Adyen payment, but it still requires
// bank account details to be set for monthly payment therefore adding
// bank account fied in custome address field (coz its easy since the code
// was already there). :)

/* @var $installer Mage_Customer_Model_Entity_Setup */
$installer = $this;
$installer->startSetup();
/* @var $addressHelper Mage_Customer_Helper_Address */
$addressHelper = Mage::helper('customer/address');
$store         = Mage::app()->getStore(Mage_Core_Model_App::ADMIN_STORE_ID);
 
/* @var $eavConfig Mage_Eav_Model_Config */
$eavConfig = Mage::getSingleton('eav/config');
 
// update customer address user defined attributes data
$attributes = array(

    'bank_account'           => array(   
        'label'    => 'Bank Account',
        'type'     => 'varchar',
        'input'    => 'text',
        'is_user_defined'   => 1,
        'is_system'         => 0,
        'is_visible'        => 1,
        'sort_order'        => 140,
        'is_required'       => 0,
        'multiline_count'   => 0,
    ),
);


foreach ($attributes as $attributeCode => $data) {
    $attribute = $eavConfig->getAttribute('customer_address', $attributeCode);
    //$attribute->setWebsite($store->getWebsite());
    $attribute->addData($data);
    $attribute->setFrontendInput('text');
    $attribute->setBackendType('varchar');
        $usedInForms = array(
            'adminhtml_customer_address',
            'customer_address_edit',
            'customer_register_address'
        );
        $attribute->setData('used_in_forms', $usedInForms);
    $attribute->save();
}


// Adding to quote and address fields.

// $installer->run("
//     ALTER TABLE {$this->getTable('sales_flat_quote_address')} ADD COLUMN `bank_account` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL AFTER `fax`;
//     ALTER TABLE {$this->getTable('sales_flat_order_address')} ADD COLUMN `bank_account` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL AFTER `fax`;
//     ");


$installer->endSetup();