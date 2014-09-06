<?php
$installer = $this;
$installer->startSetup();
$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
// $entityTypeId     = $setup->getEntityTypeId('product');
// $attributeSetId   = $setup->getDefaultAttributeSetId($entityTypeId);
// $attributeGroupId = $setup->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

$installer->addAttribute('catalog_product', 'use_accessories_for', array(
    'input'         => 'multiselect',
    'type'          => 'varchar',
    'label'         => 'Use accessories For',
    'visible'       => 1,
	'backend'       => 'eav/entity_attribute_backend_array',
    'required'      => 0,
    'user_defined'  => 1,
    'source' 		=> 'nrc/accessories'
	));
$installer->endSetup();


   

