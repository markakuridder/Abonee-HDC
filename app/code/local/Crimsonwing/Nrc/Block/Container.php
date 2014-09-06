<?php 

class Crimsonwing_Nrc_Block_Container extends Mage_Checkout_Block_Onepage_Payment_Methods
{
	public function getMethods()
    {
		//mage::log('works a');
    	$methods = $this->getData('methods');
        if (is_null($methods)) {
            $quote = $this->getQuote();
            $store = $quote ? $quote->getStoreId() : null;
            $methods = $this->helper('payment')->getStoreMethods($store, $quote);
            $total = $quote->getGrandTotal();
            foreach ($methods as $key => $method) {
            	//$this->_assignMethod($method);
                
            	if ($this->_canUseMethod($method)
                    && ($total != 0
                        || $method->getCode() == 'free'
                        || $method->getCode() == 'directdebit'
                        || ($quote->hasRecurringItems() && $method->canManageRecurringProfiles()))) {
                    $this->_assignMethod($method);
                    //mage::log($method->getCode());
                } else {
                    unset($methods[$key]);
                }
                
            }
            $this->setData('methods', $methods);
        }
        //mage::log($methods->getData());
        return $methods;
    }
}