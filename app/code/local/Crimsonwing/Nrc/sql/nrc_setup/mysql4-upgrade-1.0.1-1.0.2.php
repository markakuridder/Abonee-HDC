<?php

$installer = new Mage_Sales_Model_Resource_Setup('core_setup');
$installer->startSetup();

$installer->addAttribute('quote', 'kbo_member', array(
    'type'  => 'varchar',
));
$installer->addAttribute('order', 'kbo_member', array(
    'type'  => 'varchar',
));

$installer->endSetup();
