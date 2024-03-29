<?php
class Idev_OneStepCheckout_IndexController extends Mage_Core_Controller_Front_Action {

    public function getOnepage() {
        return Mage::getSingleton('checkout/type_onepage');
    }

    public function successAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function indexAction() {
        $quote = $this->getOnepage()->getQuote();
        if (!$quote->hasItems() || $quote->getHasError()) {
            $this->_redirect('checkout/cart');
            return;
        }
        if (!$quote->validateMinimumAmount()) {
            $error = Mage::getStoreConfig('sales/minimum_order/error_message');
            Mage::getSingleton('checkout/session')->addError($error);
            $this->_redirect('checkout/cart');
            return;
        }
        $setAddress = $this->_addDefaultAddress();
        if (!$setAddress) {
        	$error = 'Could not set default billing address for customer.';
        	Mage::getSingleton('checkout/session')->addError($error);
        	$this->_redirect('checkout/cart');
        	return;
        }
        
        $this->loadLayout();

        if(Mage::helper('onestepcheckout')->isEnterprise() && Mage::helper('customer')->isLoggedIn()){

            $customerBalanceBlock = $this->getLayout()->createBlock('enterprise_customerbalance/checkout_onepage_payment_additional', 'customerbalance', array('template'=>'onestepcheckout/customerbalance/payment/additional.phtml'));
            $customerBalanceBlockScripts = $this->getLayout()->createBlock('enterprise_customerbalance/checkout_onepage_payment_additional', 'customerbalance_scripts', array('template'=>'onestepcheckout/customerbalance/payment/scripts.phtml'));

            $rewardPointsBlock = $this->getLayout()->createBlock('enterprise_reward/checkout_payment_additional', 'reward.points', array('template'=>'onestepcheckout/reward/payment/additional.phtml', 'before' => '-'));
            $rewardPointsBlockScripts = $this->getLayout()->createBlock('enterprise_reward/checkout_payment_additional', 'reward.scripts', array('template'=>'onestepcheckout/reward/payment/scripts.phtml', 'after' => '-'));

            $this->getLayout()->getBlock('choose-payment-method')
            ->append($customerBalanceBlock)
            ->append($customerBalanceBlockScripts)
            ->append($rewardPointsBlock)
            ->append($rewardPointsBlockScripts)
            ;
        }

        $this->renderLayout();
    }
    
    /**
     * Adding Default Billing address to quote when customer is logged in.
     * @return boolean
     */
    protected function _addDefaultAddress()
    {
    	try {
    		if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
    			return true;
    		}
    		$defaultBillingAddress = Mage::getSingleton('customer/session')
    		->getCustomer()->getDefaultBillingAddress();
    
    		$billingAddress = Mage::getModel('sales/quote_address')
    		->importCustomerAddress($defaultBillingAddress);
    
    		Mage::getSingleton('checkout/session')
    		->getQuote()->setBillingAddress($billingAddress)->save();
    		 
    		$customerDob = Mage::getSingleton('customer/session')
    		->getCustomer()->getData('dob');
    
    		Mage::getSingleton('checkout/session')
    		->getQuote()->setCustomerDob($customerDob)->save();
    
    		return true;
    
    	} catch (Exception $e) {
    		Mage::printException($e);
    		return false;
    	}
    	 
    }
    
    
}