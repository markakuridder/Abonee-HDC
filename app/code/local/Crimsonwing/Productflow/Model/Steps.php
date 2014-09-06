<?php

class Crimsonwing_Productflow_Model_Steps
{
    const GLOB_BRACE = 16; // 1 << 4
    const GLOB_NOCASE = 64; // 1 << 6
    protected $_debug = false;
    protected $_step = 3;

    protected function _debugOutput($string, $step = null)
    {
        if ($this->_debug) {
            if (!$step || ($this->_step && $step == $this->_step)) {
                echo $string . '<br />';
            }
        }
    }

    public function getProductForStep($step)
    {
        /** @var Mage_Catalog_Model_Resource_Product_Collection */
        $productCollection = Mage::getResourceModel('catalog/product_collection')
            ->addStoreFilter()
            ->setVisibility(Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds())
            ->addAttributeToSelect('name')
            ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
            ->addAttributeToFilter('productflow_step', $step)
        ;

        $productCount = count($productCollection);
        $this->_debugOutput('Count: ' . $productCount, $step);
        if ($productCount == 1) {
            return Mage::getModel('catalog/product')->load($productCollection->getFirstItem()->getId());
        } elseif ($productCount > 1 && $step > 1 && Mage::helper('productflow')->getAttributeMatch()) {
            $attributeCodes = explode(',', Mage::helper('productflow')->getAttributeMatch());
            $this->_debugOutput('Attribute codes: ' . implode(', ', $attributeCodes), $step);
            $productToMatch = $this->getSimpleProductInCartForStep($step - 1);
            $product = null;
            if ($productToMatch) {
                $productToMatch = Mage::getModel('catalog/product')->load($productToMatch->getId());
                $this->_debugOutput(sprintf('Product for step %d: SKU %s (%s)', $step - 1, $productToMatch->getSku(), $productToMatch->getName()), $step);
                $secondProductCollection = Mage::getResourceModel('catalog/product_collection')
                    ->addStoreFilter()
                    ->setVisibility(Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds())
                    ->addAttributeToSelect('name')
                    ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                    ->addAttributeToFilter('productflow_step', $step)
                ;
                foreach ($attributeCodes as $attributeCode) {
                    $this->_debugOutput(sprintf("Add attribute '%s' to collection with value '%s'", $attributeCode, $productToMatch->getData($attributeCode)));
                    $secondProductCollection->addAttributeToFilter($attributeCode, $productToMatch->getData($attributeCode));
                }
                if (count($secondProductCollection) == 1) {
                    $product = $secondProductCollection->getFirstItem();
                    $this->_debugOutput(sprintf('FOUND product: SKU %s (%s)', $product->getSku(), $product->getName()), $step);
                    return Mage::getModel('catalog/product')->load($product->getId());
                }
            }
        }
        Mage::throwException('Couldn\'t determine product for step ' . $step);
    }


    public function getSimpleProductInCartForStep($step)
    {
        foreach (Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection() as $item) {
            if (!$item->getParentItemId() && $item->getProduct()->getProductflowStep() == $step) {
                // now we have the main item. if it has child items: check them
                if (count($item->getChildren())) {
                    $this->_debugOutput('Item has children');
                    $childItem = @reset($item->getChildren());
                    return $childItem->getProduct();
                }
                return $item;
            }
        }
        return null;
    }


   /**
    * Implementation of glob() for string matching
    *
    * This is different from fnmatch() because of the support
    * for GLOB_BRACE (what would have been FNM_EXTMATCH if php
    * had support for it). All filesystem related flags are not
    * supported.
    *
    * @param string $pattern
    * @param string $subject
    * @param int $flags
    */
   function _globStr($pattern, $subject, $flags = 0x0000) {
       $rxci = ($flags & self::GLOB_NOCASE) ? 'i' : '';

       $pat = preg_quote($pattern, '/');
       $pat = strtr($pat, array(
           '\\*' => '.*?',
           '\\?' => '.',
           '\\[' => '[',
           '\\]' => ']',
       ));
       if ($flags & self::GLOB_BRACE) {
           $pat = preg_replace("/\\\{(.+?)\\\}/e", 'strtr("(?:$1)", ",", "|")', $pat);
       }
       return preg_match('/^' . $pat . '$/' . $rxci, $subject);
   }

}
