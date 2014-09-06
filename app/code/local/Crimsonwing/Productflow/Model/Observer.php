<?php

class Crimsonwing_Productflow_Model_Observer
{

    /**
    * Redirects the client to the menu page after adding something to the cart
    *
    * available in event: product, request, response
    *
    * event: checkout_cart_add_product_complete
    * @param mixed $observer
    */
    public function processAddToCart(Varien_Event_Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $step = $product->getProductflowStep();

        if ($step) {
            $stepProperties = $this->_getHelper()->getPropertiesForStep($step);

            // setNoCartRedirect() also suppresses the success message
            Mage::getSingleton('checkout/session')->setNoCartRedirect(true);
            $this->_redirectToNextStep($step, $observer->getEvent()->getResponse());
        }
        // Still a possibility to set a message, nut disabled for now
        // Mage::getSingleton('customer/session')->addSuccess('message'));
    }


    /**
    * Redirect to the next step (product or cart/checkout)
    *
    * This function only sets the header, it doesn't send it and doesn't exit
    * You can use $response->sendResponse(); exit;
    *
    * @param int $currentStep
    */
    protected function _redirectToNextStep($currentStep, Mage_Core_Controller_Response_Http $response)
    {
        $maxStep = $this->_getHelper()->getMaxStep();
        $nextStep = $currentStep + 1;
        if ($nextStep <= $maxStep) {
            $nextProduct = Mage::getSingleton('productflow/steps')->getProductForStep($nextStep);
            if ($nextProduct && $nextProduct->getId()) {
                $response->setRedirect($nextProduct->getProductUrl());
            }
        } else {
            $this->_redirectToCart($response);
        }
    }


    /**
    * Redirects to cart or checkout, depending on setting
    *
    * This function only sets the header, it doesn't send it and doesn't exit
    * You can use $response->sendResponse(); exit;
    *
    * @param Mage_Core_Controller_Response_Http $response
    */
    protected function _redirectToCart(Mage_Core_Controller_Response_Http $response)
    {
        if ($this->_getHelper()->getSkipCart()) {
            if (Mage::helper('core')->isModuleEnabled('Idev_OneStepCheckout')) {
                $response->setRedirect(Mage::getUrl('onestepcheckout'));
            } else {
                $response->setRedirect(Mage::getUrl('checkout/onepage'));
            }
        } else {
            $response->setRedirect(Mage::getUrl('checkout/cart'));
        }
    }


    /**
    * Removes the same products from the shopping cart
    *
    * If the reference product is a grouped product, then also the associated products will
    * be removed if present in the cart.
    *
    * There is another approach possible: check the step of the reference product and check
    * the step of each item in the cart (by checking the product or setting it as a custom
    * option when adding it to the cart) and remove the cart item if they have matching steps.
    * For now, the chosen approach suffices.
    *
    * @param Mage_Catalog_Model_Product $product
    */
    protected function _removeAlikeProductsFromCart(Mage_Catalog_Model_Product $product)
    {
        foreach (Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection() as $item) {
            if ($product->getId() == $item->getProductId()) {
                // this also removes child items in the case of a configurable product
                Mage::getSingleton('checkout/cart')->removeItem($item->getId())->save();
            }
            if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
                // all products associated with this grouped product must be removed
                foreach ($product->getTypeInstance(true)->getAssociatedProducts($product) as $associatedProduct) {
                    if ($associatedProduct->getId() == $item->getProductId()) {
                        Mage::getSingleton('checkout/cart')->removeItem($item->getId())->save();
                    }
                }
            }
        }
    }


    /**
    * Checks if steps are filled upto and including $lastStepToCheck
    *
    * @param int $lastStepToCheck
    * @return bool  TRUE if everything's alright, exits and redirects if there's a missing step
    */
    protected function _checkSteps($lastStepToCheck)
    {
        for ($step = 1; $step <= $lastStepToCheck; $step++) {
            $stepInfo = $this->_getHelper()->getPropertiesForStep($step);
            if ($stepInfo->getMinQty() > 0) {
                $missing = true;
                foreach (Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection() as $item) {
                    if ($item->getProduct()->getProductflowStep() == $step) {
                        $missing = false;
                        break;
                    }
                }
                if ($missing) {
                    $redirectProduct = Mage::getSingleton('productflow/steps')->getProductForStep($step);
                    Mage::app()->getFrontController()->getResponse()->setRedirect($redirectProduct->getProductUrl())->sendResponse();
                    exit;
                }
            }
            // check max qty
            if ($stepInfo->getMaxQty() > 0) {
                $qty = 0;
                /** @var Mage_Sales_Model_Quote */
                $quote = Mage::getSingleton('checkout/session')->getQuote();
                $changed = false;
                foreach ($quote->getItemsCollection() as $item) {
                    /** @var Mage_Sales_Model_Quote_Item */
                    if ($item->getProduct()->getProductflowStep() == $step) {
                        $qty++;
                        if ($qty > $stepInfo->getMaxQty()) {
                            $changed = true;
                            $quote->removeItem($item->getId());
                        }
                    }
                }
                if ($changed) {
                    $quote->save();
                }
            }
        }
        return true;
    }


    /**
    * Remove alike products from cart
    *
    * event: catalog_controller_product_view (called from Mage_Catalog_Helper_Product_View)
    * @see   _removeAlikeProductsFromCart()
    * @param Varien_Event_Observer $observer
    */
    public function productView(Varien_Event_Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();

        if ($step = $product->getProductflowStep()) {
            if ($step > 1) {
                $this->_checkSteps($step - 1);
            }

            $stepProperties = $this->_getHelper()->getPropertiesForStep($step);
            if ($stepProperties->getClearCart() == Crimsonwing_Productflow_Helper_Data::CLEAR_CART_VIEW_STEP) {
                $this->_removeAlikeProductsFromCart($product);
            } elseif ($stepProperties->getClearCart() == Crimsonwing_Productflow_Helper_Data::CLEAR_CART_VIEW_ALL) {
                Mage::getSingleton('checkout/cart')->truncate();
                Mage::getSingleton('checkout/cart')->save();
            }
        }
    }


    /**
    * Get the productflow helper
    *
    * @return Crimsonwing_Productflow_Helper_Data
    */
    protected function _getHelper()
    {
        return Mage::helper('productflow');
    }


    /**
    * Make it possible to add 0 products (skip a step by leaving qty out)
    *
    * Handles (at least - tested) grouped, simple and configurable products
    *
    * event: controller_action_predispatch_checkout_cart_add
    * @param Varien_Event_Observer $observer
    */
    public function allowZeroQty(Varien_Event_Observer $observer)
    {
        /** @var Mage_Core_Controller_Varien_Action */
        $action = $observer->getEvent()->getControllerAction();
        if ($productId = $action->getRequest()->getParam('product')) {
            $product = Mage::getModel('catalog/product')->load($productId);
            // is there a minimum qty for this step?
            if ($step = $product->getProductflowStep()) {
                $stepInfo = $this->_getHelper()->getPropertiesForStep($step);
                if (!$stepInfo->getMinQty()) {
                    $hasProducts = false;
                    if ($product->getId() && $product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
                        $superGroup = $action->getRequest()->getParam('super_group');
                        if ($superGroup) {
                            foreach ($superGroup as $qty) {
                                if ($qty > 0) {
                                    $hasProducts = true;
                                    break;
                                }
                            }
                        }
                        if (!$hasProducts) {
                            // there are no products given for adding; the grouped product type will complain
                            // so redirect to the cart and stop processing the cart adding
                            $response = Mage::app()->getFrontController()->getResponse();
                            $this->_redirectToNextStep($step, $response);
                            $response->sendResponse();
                            exit;
                        } else {
                            // there are products in the request
                            // things can be handled as normal
                        }
                    } else {
                        // make it possible to enter '0' in the qty box and thus skip the step
                        if ($action->getRequest()->getParam('qty') == 0) {
                            $response = Mage::app()->getFrontController()->getResponse();
                            $this->_redirectToNextStep($step, $response);
                            $response->sendResponse();
                            exit;
                        }
                    }
                } else {
                    // there is a min qty, so the grouped may not be skipped
                }
            }
        }
    }


    /**
    * Check if the shopping cart has every required step filled
    *
    * If a step is not filled, the client gets redirected to that step
    * Redirects to checkout if set in configuration
    * This function does nothing if 'skip shopping cart' is not set in config
    *
    * event: controller_action_predispatch_checkout_cart_index
    * @param Varien_Event_Observer $observer
    */
    public function checkShoppingCartOnCart(Varien_Event_Observer $observer)
    {
        if ($this->_getHelper()->getSkipCart()) {
            $this->_checkSteps($this->_getHelper()->getMaxStep());
            $response = Mage::app()->getFrontController()->getResponse();
            $this->_redirectToCart($response); // the same config gets checked and client gets redirected to checkout
            $response->sendResponse();
        }
    }


    /**
    * Check if the shopping cart has every required step filled
    *
    * If a step is not filled, the client gets redirected to that step
    *
    * event: controller_action_predispatch_checkout_onepage_index
    * @param Varien_Event_Observer $observer
    */
    public function checkShoppingCartOnCheckout(Varien_Event_Observer $observer)
    {
        $this->_checkSteps($this->_getHelper()->getMaxStep());
    }


}