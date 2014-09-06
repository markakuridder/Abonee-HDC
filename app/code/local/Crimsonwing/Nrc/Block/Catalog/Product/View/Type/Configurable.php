<?php

class Crimsonwing_Nrc_Block_Catalog_Product_View_Type_Configurable extends
    Mage_Catalog_Block_Product_View_Type_Configurable
{

    /**
     * Of course this could be done cleaner by first calling parent::getJsonConfig().
     * In a next project, we'll definitely do that. But for now, this project is not likely
     * to ever upgrade it's Magento installation.
     *
     * See git's history for what exactly has been changed (it's first committed in it's
     * original form, then modified, then again committed).
     *
     * The name of the function is changed because it now supports parameters and the original
     * class does not. It generates a php warning if the number or type of parameters changes
     * if you extend a class.
     *
     * @return string
     */
    public function getJsonConfigExt($imageWidth = 250, $imageHeight = null, $imagesLevel = 1)
    {
        $attributes = array();
        $options    = array();
        $store      = $this->getCurrentStore();
        $taxHelper  = Mage::helper('tax');
        $currentProduct = $this->getProduct();
        $imageUrl = false;

        $preconfiguredFlag = $currentProduct->hasPreconfiguredValues();
        if ($preconfiguredFlag) {
            $preconfiguredValues = $currentProduct->getPreconfiguredValues();
            $defaultValues       = array();
        }

        $images = array();
        foreach ($this->getAllowProducts() as $product) {
            $productId  = $product->getId();
            $attributeLevel = 1;
            $key = array();
            foreach ($this->getAllowAttributes() as $attribute) {
            	$productAttribute   = $attribute->getProductAttribute();
            	$productAttributeId = $productAttribute->getId();
            	$attributeValue     = $product->getData($productAttribute->getAttributeCode());
            	if (!isset($options[$productAttributeId])) {
            		$options[$productAttributeId] = array();
            	}
            	if (!isset($images[$productAttributeId])) {
            		$images[$productAttributeId] = array();
            	}
            
            	if (!isset($options[$productAttributeId][$attributeValue])) {
            		$options[$productAttributeId][$attributeValue] = array();
            	}
            	if ($attributeLevel <= $imagesLevel) {
            		$key [] = $attributeValue;
            	}
            	if ($product->getImage() && $product->getImage() != 'no_selection') {
	            	$imageUrl = (string) Mage::helper('catalog/image')
	            			->init($product, 'image')->resize($imageWidth, $imageHeight);
            	}
            	
            	if ($attributeLevel == 2) {
            		$keyString = implode("_", $key);
//             		$imageUrl = (string) Mage::helper('catalog/image')
//             			->init($product, 'image')->resize($imageWidth, $imageHeight);
					if ($imageUrl) {
	            		$images [$keyString] = $imageUrl;
					}
            	}
            
            	$options[$productAttributeId][$attributeValue][] = $productId;
            	$attributeLevel++;
            }
            
            
            
        }

        Mage::log($images);
//         exit;
        $this->_resPrices = array(
            $this->_preparePrice($currentProduct->getFinalPrice())
        );

        foreach ($this->getAllowAttributes() as $attribute) {
            $productAttribute = $attribute->getProductAttribute();
            $attributeId = $productAttribute->getId();
            $info = array(
               'id'        => $productAttribute->getId(),
               'code'      => $productAttribute->getAttributeCode(),
               'label'     => $attribute->getLabel(),
               'options'   => array()
            );

            $optionPrices = array();
            $prices = $attribute->getPrices();
            if (is_array($prices)) {
                foreach ($prices as $value) {
                    if(!$this->_validateAttributeValue($attributeId, $value, $options)) {
                        continue;
                    }
                    $currentProduct->setConfigurablePrice(
                        $this->_preparePrice($value['pricing_value'], $value['is_percent'])
                    );
                    $currentProduct->setParentId(true);
                    Mage::dispatchEvent(
                        'catalog_product_type_configurable_price',
                        array('product' => $currentProduct)
                    );
                    $configurablePrice = $currentProduct->getConfigurablePrice();

                    if (isset($options[$attributeId][$value['value_index']])) {
                        $productsIndex = $options[$attributeId][$value['value_index']];
                    } else {
                        $productsIndex = array();
                    }

                    $info['options'][] = array(
                        'id'        => $value['value_index'],
                        'label'     => $value['label'],
                        'price'     => $configurablePrice,
                        'oldPrice'  => $this->_preparePrice($value['pricing_value'], $value['is_percent']),
                        'image'     => (isset($images[$attributeId][$value['value_index']])) ? $images[$attributeId][$value['value_index']] : null,
                        'products'  => $productsIndex,
                    );
                    $optionPrices[] = $configurablePrice;
                    //$this->_registerAdditionalJsPrice($value['pricing_value'], $value['is_percent']);
                }
            }
            /**
             * Prepare formated values for options choose
             */
            foreach ($optionPrices as $optionPrice) {
                foreach ($optionPrices as $additional) {
                    $this->_preparePrice(abs($additional-$optionPrice));
                }
            }
            if($this->_validateAttributeInfo($info)) {
               $attributes[$attributeId] = $info;
            }

            // Add attribute default value (if set)
            if ($preconfiguredFlag) {
                $configValue = $preconfiguredValues->getData('super_attribute/' . $attributeId);
                if ($configValue) {
                    $defaultValues[$attributeId] = $configValue;
                }
            }
        }

        $taxCalculation = Mage::getSingleton('tax/calculation');
        if (!$taxCalculation->getCustomer() && Mage::registry('current_customer')) {
            $taxCalculation->setCustomer(Mage::registry('current_customer'));
        }

        $_request = $taxCalculation->getRateRequest(false, false, false);
        $_request->setProductClassId($currentProduct->getTaxClassId());
        $defaultTax = $taxCalculation->getRate($_request);

        $_request = $taxCalculation->getRateRequest();
        $_request->setProductClassId($currentProduct->getTaxClassId());
        $currentTax = $taxCalculation->getRate($_request);

        $taxConfig = array(
            'includeTax'        => $taxHelper->priceIncludesTax(),
            'showIncludeTax'    => $taxHelper->displayPriceIncludingTax(),
            'showBothPrices'    => $taxHelper->displayBothPrices(),
            'defaultTax'        => $defaultTax,
            'currentTax'        => $currentTax,
            'inclTaxTitle'      => Mage::helper('catalog')->__('Incl. Tax')
        );

        $config = array(
            'attributes'        => $attributes,
            'template'          => str_replace('%s', '#{price}', $store->getCurrentCurrency()->getOutputFormat()),
//            'prices'          => $this->_prices,
            'basePrice'         => $this->_registerJsPrice($this->_convertPrice($currentProduct->getFinalPrice())),
            'oldPrice'          => $this->_registerJsPrice($this->_convertPrice($currentProduct->getPrice())),
            'productId'         => $currentProduct->getId(),
            'chooseText'        => Mage::helper('catalog')->__('Choose an Option...'),
            'taxConfig'         => $taxConfig
        );

        if ($preconfiguredFlag && !empty($defaultValues)) {
            $config['defaultValues'] = $defaultValues;
        }

        $config = array_merge($config, $this->_getAdditionalConfig());
		$config ['images'] = $images;
// 		Mage::log($config);exit;
        return Mage::helper('core')->jsonEncode($config);
    }

}