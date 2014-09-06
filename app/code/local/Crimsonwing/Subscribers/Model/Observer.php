<?php

class Crimsonwing_Subscribers_Model_Observer
{

	public function redirectToSecondPage($observer)
	{
		$steps = Mage::getSingleton ( 'productflow/steps' );
		$secondStep = $steps->getProductForStep ( 1 );
		$afterUrl = $secondStep->getProductUrl ();
		echo header("Location:" . $afterUrl);exit;
	}
	
    /**
    * Apply the coupon code from session to the active quote
    *
    * BEWARE: this function doesn't clear the session value, because the coupon code gets
    * unset again when the cart gets emptied. But this also means that the original Magento
    * functionality can get unstable. I try to avoid problems to check for an old coupon
    * code when applying the session coupon code.
    *
    * event: checkout_cart_product_add_after
    * @return void
    */
    public function applyCouponFromSession()
    {
        $couponCode = trim(Mage::getSingleton('checkout/session')->getCouponCode());
        $oldCouponCode = $this->_getQuote()->getCouponCode();
        $quoteId = $this->_getQuote()->getId();

        if ($couponCode && !$oldCouponCode) {
            Mage::log('[Q' . $quoteId . '] Applying new code to quote: ' . $couponCode, null, 'codes.log');
            $this->_getQuote()
                ->getShippingAddress()
                ->setCollectShippingRates(true);

            $this->_getQuote()
                ->setCouponCode(strlen($couponCode) ? $couponCode : '')
                ->collectTotals()
                ->save();

            // this same logic is in Crimsonwing_Subscribers_AbonneeController
            if ($couponCode == $this->_getQuote()->getCouponCode()) {
                // disable active subscription checking in checkout
                Mage::log('[Q' . $quoteId . '] Set noActiveSubscriptionCheckFlag based on coupon code ' . $couponCode, null, 'codes.log');
                Mage::getSingleton('checkout/session')->setNoActiveSubscriptionCheckFlag(true);
            } else {
                // enable active subscription checking in checkout
                Mage::getSingleton('checkout/session')->setNoActiveSubscriptionCheckFlag(false);
                Mage::log('[Q' . $quoteId . '] REJECTED code : ' . $couponCode, null, 'codes.log');
                Mage::log(sprintf('ERROR: There was an error while applying voucher code %s', $couponCode));
            }
        }
        if ($oldCouponCode) {
            Mage::log('[Q' . $quoteId . '] Set noActiveSubscriptionCheckFlag based on old coupon code', null, 'codes.log');
            Mage::getSingleton('checkout/session')->setNoActiveSubscriptionCheckFlag(true);
        }
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