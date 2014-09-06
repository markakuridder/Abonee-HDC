<?php

/** @var Mage_Catalog_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

/**
 * Add 'nrc_maandbedrag' attribute to the 'eav/attribute' table
 */
$installer->addAttribute('catalog_product', 'nrc_maandbedrag', array(
    'group'             => 'General',
    'type'              => 'decimal',
    'backend'           => '',
    'frontend'          => '',
    'label'             => 'NRC: Maandbedrag',
    'input'             => 'price',
    'class'             => '',
    'source'            => '',
    'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'           => true,
    'required'          => false,
    'user_defined'      => false,
    'searchable'        => false,
    'filterable'        => false,
    'comparable'        => true,
    'visible_on_front'  => true,
    'unique'            => false,
    'apply_to'          => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
    'is_configurable'   => false
));

/**
 * Add 'nrc_voordeel' attribute to the 'eav/attribute' table
 */
$installer->addAttribute('catalog_product', 'nrc_voordeel', array(
    'group'             => 'General',
    'type'              => 'decimal',
    'backend'           => '',
    'frontend'          => '',
    'label'             => 'NRC: Voordeel',
    'input'             => 'price',
    'class'             => '',
    'source'            => '',
    'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'           => true,
    'required'          => false,
    'user_defined'      => false,
    'searchable'        => false,
    'filterable'        => false,
    'comparable'        => true,
    'visible_on_front'  => true,
    'unique'            => false,
    'apply_to'          => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
    'is_configurable'   => false
));

$installer->endSetup();
