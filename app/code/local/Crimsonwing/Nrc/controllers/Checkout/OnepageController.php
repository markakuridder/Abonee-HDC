<?php
require_once 'Mage/Checkout/controllers/OnepageController.php';
class Crimsonwing_Nrc_Checkout_OnepageController extends Mage_Checkout_OnepageController
{
	public function successAction()
    {    
    	
    	$session = $this->getOnepage()->getCheckout();
        if (!$session->getLastSuccessQuoteId()) {
            $this->_redirect('checkout/cart');
            return;
        }

        $lastQuoteId = $session->getLastQuoteId();
        $lastOrderId = $session->getLastOrderId();
        //CUSTOM Z
        //Tracking of process flow in order to see which orders came on this page
        mage::log($lastOrderId);
        
        $order = Mage::getModel('sales/order')->load($lastOrderId);
        $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_COMPLETE); 
        $order->setStatus(Mage_Sales_Model_Order::STATE_COMPLETE, true)->save();
        
        $lastRecurringProfiles = $session->getLastRecurringProfileIds();
        if (!$lastQuoteId || (!$lastOrderId && empty($lastRecurringProfiles))) {
            $this->_redirect('checkout/cart');
            return;
        }

        $session->clear();
        $this->loadLayout();
        $this->_initLayoutMessages('checkout/session');
        Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($lastOrderId)));
        $this->renderLayout();
    }
}