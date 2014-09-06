<?php
class Crimsonwing_Nrc_Model_Accessories extends Mage_Eav_Model_Entity_Attribute_Source_Abstract {

	CONST ATT_SET_ID = '64';
	
	public function getAllOptions($withEmpty = true)
	{
		
		$collection = Mage::getModel('catalog/product')->getCollection();
		$collection->getSelect()->where('attribute_set_id = ?', self::ATT_SET_ID);
		$options = array();
		
		foreach ($collection as $_product) {
			$_product->load($_product->getId());
			$temp = array('label' => $_product->getName(), 'value' => $_product->getId());
			array_push($options, $temp);
			unset($temp);
		}
		
		array_unshift($options, array('label' => '', 'value' => ''));
		return $options;
	}

}