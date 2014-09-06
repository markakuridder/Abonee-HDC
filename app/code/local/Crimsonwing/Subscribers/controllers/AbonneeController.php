<?php

class Crimsonwing_Subscribers_AbonneeController extends Mage_Core_Controller_Front_Action
{

    public function indexAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages(array('core/session', 'customer/session', 'checkout/session'));
        $this->getLayout()->getBlock('subscriber.form')
            ->setFormAction(Mage::getUrl('*/*/voucherPost'));
        $this->getLayout()->getBlock('head')
            ->setRobots('NOINDEX,FOLLOW');
        return $this->renderLayout();
    }


    public function voucherPostAction()
    {
        $couponCode = (string)$this->getRequest()->getParam('voucher_code');
        $subscriberNumber = (string)$this->getRequest()->getParam('subscriber_number');

        if (!$couponCode) {
            $this->_getSession()->addError(Mage::helper('subscribers')->__('Voucher code is a required field'));
        } elseif (!$subscriberNumber) {
            $this->_getSession()->addError(Mage::helper('subscribers')->__('Subscriber Number is a required field'));
        } else {
            $coupon = Mage::getModel('salesrule/coupon')->load($couponCode, 'code');

            if ($coupon->getId()) {
                $timesUsed = intval($coupon->getTimesUsed());
                $usageLimit = intval($coupon->getUsageLimit());

                if ($timesUsed >= $usageLimit) {
                    Mage::log('Duplicate code entered: ' . $couponCode, null, 'codes.log');
                    $this->_getSession()->addError(Mage::helper('subscribers')->__('This voucher code has already been used.'));
                } else {
                    Mage::getSingleton('checkout/session')->setCouponCode($couponCode);
                    Mage::log('Accepted code: ' . $couponCode . '; added to session', null, 'codes.log');
                    $this->_getSession()->addSuccess(Mage::helper('subscribers')->__('The voucher code has been accepted.'));

                    // try to apply it right away. will fail if there are no items in the quote
                    // this same logic is in Crimsonwing_Subscribers_Model_Observer
                    $this->_getQuote()
                        ->getShippingAddress()
                        ->setCollectShippingRates(true);

                    $this->_getQuote()
                        ->setCouponCode(strlen($couponCode) ? $couponCode : '')
                        ->collectTotals()
                        ->save();

                    if ($couponCode == $this->_getQuote()->getCouponCode()) {
                        Mage::log('[Q' . $this->_getQuote()->getId() . '] Set noActiveSubscriptionCheckFlag based on coupon code ' . $couponCode, null, 'codes.log');
                        Mage::getSingleton('checkout/session')->setNoActiveSubscriptionCheckFlag(true);
                    } else {
                        Mage::log('[Q' . $this->_getQuote()->getId() . '] can\'t apply coupon code right away: ' . $this->_getQuote()->getCouponCode(), null, 'codes.log');
                    }

                    // redirect to cart. the 'productflow' extension will redirect to the next available step
                    $this->_redirect('checkout/cart');
                    return;
                }
            } else {
                Mage::log('Invalid code entered: ' . $couponCode, null, 'codes.log');
                Mage::getSingleton('popupmessage/message')->setMessageStaticBlockId(Mage::helper('subscribers')->getInvalidVoucherCodeBlockId());
            }
        }
        $this->_redirect('*/*/index');
    }


    /**
    * Get Customer session
    *
    * @return Mage_Customer_Model_Session
    */
    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
    * Get active quote
    *
    * @return Mage_Sales_Model_Quote
    */
    protected function _getQuote()
    {
        return Mage::getSingleton('checkout/cart')->getQuote();
    }

}