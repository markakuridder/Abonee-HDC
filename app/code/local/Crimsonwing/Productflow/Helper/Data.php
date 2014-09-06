<?php

class Crimsonwing_Productflow_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_STEPS = 'productflow/general/steps';
    const XML_PATH_MAX_STEPS = 'productflow/general/max_step';
    const XML_PATH_SKIP_CART = 'productflow/general/skip_cart';
    const XML_PATH_ATTRIBUTE_MATCH = 'productflow/general/attribute_match';

    const CLEAR_CART_VIEW_STEP = 1; // clear products belonging to same step from cart on product view
    const CLEAR_CART_VIEW_ALL = 2; // clear all products from cart on product view
    //const CLEAR_CART_ADD_STEP = 3; // clear products belonging to same step from cart on add to cart  - difficult! leave it for now

    /**
    * Get properties for a step, as set in configuration
    *
    * @param int $step
    * @return Varien_Object
    */
    public function getPropertiesForStep($step)
    {
        if (!$stepInfo = $this->_getSerializedArrayConfig(self::XML_PATH_STEPS, 'step', $step)) {
            Mage::throwException(sprintf('There is no configuration for step %d', (int)$step));
        }
        return $stepInfo;
    }


    /**
    * Get max_step configuration setting
    *
    * @return int
    */
    public function getMaxStep()
    {
        return (int)Mage::getStoreConfig(self::XML_PATH_MAX_STEPS);
    }


    /**
    * Get attribute_match configuration setting
    *
    * @return string
    */
    public function getAttributeMatch()
    {
        return Mage::getStoreConfig(self::XML_PATH_ATTRIBUTE_MATCH);
    }


    /**
    * Get skip_cart configuration setting
    *
    * @return bool
    */
    public function getSkipCart()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_SKIP_CART);
    }


    /**
    * Get a value from a serialized config value
    *
    * If $searchKey has a value, it returns the matching row of the array in a Varien_Object, or false.
    * If $searchKey has no value, it returns the entire array, consisting of Varien_Objects
    *
    * @param mixed $path  The config path to search for
    * @param mixed $searchKey  The name of the value to search for (e.g. 'payment_method_id')
    * @param mixed $searchValue The value to match (e.g. 'tablerate_bestway')
    * @return Varien_Object|Array
    */
    protected function _getSerializedArrayConfig($path, $searchKey = null, $searchValue = null)
    {
        $settings = @unserialize(Mage::getStoreConfig($path));
        if (!$settings) {
            return false;
        }
        if (is_array($settings)) {
            if (is_null($searchKey)) {
                $result = array();
                foreach ($settings as $setting) {
                    $result[] = new Varien_Object($setting);
                }
                return $result;
            } else {
                foreach ($settings as $setting) {
                    if (isset($setting[$searchKey]) && $setting[$searchKey] == $searchValue) {
                        return new Varien_Object($setting);
                    }
                }
            }
        }
        return false;
    }

}