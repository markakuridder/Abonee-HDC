<?php
$installer = $this;

$installer->startSetup();

/**
	Add Affiliate code to Order
*/
$attribute  = array(
		'type'          => 'varchar',
		'backend_type'  => 'text',
		'frontend_input' => 'text',
		'is_user_defined' => true,
		'label'         => 'Affiliate Code',
		'visible'       => true,
		'required'      => false,
		'user_defined'  => true,
		'searchable'    => true,
		'filterable'    => true,
		'comparable'    => true,
		'default'       => 0
);
$installer->addAttribute('order', 'affiliate_code', $attribute);

$table = $this->getTable('sales_flat_order');
$query = 'ALTER TABLE `' . $table . '` ADD COLUMN `affiliate_code` VARCHAR(50) CHARACTER SET utf8 DEFAULT NULL';
$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
$connection->query($query);

/**
 * Add Attributes For Customers
 */

$attribute  = array(
		'type'          => 'varchar',
		'backend_type'  => 'text',
		'frontend_input' => 'text',
		'is_user_defined' => true,
		'label'         => 'Affiliate Code',
		'visible'       => true,
		'required'      => false,
		'user_defined'  => true,
		'searchable'    => true,
		'filterable'    => true,
		'comparable'    => true,
		'default'       => 0
);

$installer->addAttribute('customer', 'affiliate_code', $attribute);


$customerAttribute = Mage::getModel('customer/attribute')->loadByCode('customer', 'affiliate_code');
$forms=array('customer_account_edit','adminhtml_customer');
$customerAttribute->setData('used_in_forms', $forms);
$customerAttribute->save();



$attribute  = array(
		'type'          => 'varchar',
		'backend_type'  => 'text',
		'frontend_input' => 'text',
		'is_user_defined' => true,
		'label'         => 'Subscription',
		'visible'       => true,
		'required'      => false,
		'user_defined'  => true,
		'searchable'    => true,
		'filterable'    => true,
		'comparable'    => true,
		'default'       => 0
);
$installer->addAttribute('customer', 'subscription_code', $attribute);

$customerAttribute = Mage::getModel('customer/attribute')->loadByCode('customer', 'subscription_code');
$forms=array('customer_account_edit','adminhtml_customer');
$customerAttribute->setData('used_in_forms', $forms);
$customerAttribute->save();

$attribute  = array(
		'type'          => 'varchar',
		'backend_type'  => 'text',
		'frontend_input' => 'text',
		'is_user_defined' => true,
		'label'         => 'Bank Acc Nr.',
		'visible'       => true,
		'required'      => false,
		'user_defined'  => true,
		'searchable'    => true,
		'filterable'    => true,
		'comparable'    => true,
		'default'       => 0
);
$installer->addAttribute('customer', 'bank_account_nr', $attribute);

$customerAttribute = Mage::getModel('customer/attribute')->loadByCode('customer', 'bank_account_nr');
$forms=array('customer_account_edit','adminhtml_customer');
$customerAttribute->setData('used_in_forms', $forms);
$customerAttribute->save();

$this->endSetup();