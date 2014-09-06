<?php
/**
 *  OneStepCheckout main block
 *  @author Jone Eide <mail@onestepcheckout.com>
 *  @copyright Jone Eide <mail@onestepcheckout.com>
 *
 */
class Crimsonwing_Nrc_Block_Checkout extends Idev_OneStepCheckout_Block_Checkout  {

    protected function _saveOrder()
    {
    	// Hack to fix weird Magento payment behaviour
        $payment = $this->getRequest()->getPost('payment', false);
        if($payment) {
            /**
             * A fix for common one big form problem
             * we rename the fields in template and iterate over subarrays
             * to see if there's any values and set them to main scope
             */
            foreach($payment as $value){
                if(is_array($value) && !empty($value)){
                    foreach($value as $key => $realValue){
                        if(!empty($realValue)){
                            $payment[$key]=$realValue;
                        }
                    }
                }
            }

            /**
             * unset unnecessary fields
             */
            foreach ($payment as $key => $value){
                if(is_array($value)){
                    unset($payment[$key]);
                }
            }
            $this->getOnepage()->getQuote()->getPayment()->importData($payment);

            $ccSaveAllowedMethods = array('ccsave');
            $method = $this->getOnepage()->getQuote()->getPayment()->getMethodInstance();

            if(in_array($method->getCode(), $ccSaveAllowedMethods)){
                $info = $method->getInfoInstance();
                $info->setCcNumberEnc($info->encrypt($info->getCcNumber()));
            }

        }


        try {
            $this->getOnepage()->getQuote()->setTotalsCollectedFlag(false)->collectTotals();
            $order = $this->getOnepage()->saveOrder();
        } catch(Exception $e)   {
            //need to activate
            $this->getOnepage()->getQuote()->setIsActive(true);
            //need to recalculate
            $this->getOnepage()->getQuote()->getShippingAddress()->setCollectShippingRates(true)->collectTotals();
            $error = $e->getMessage();
            $this->formErrors['unknown_source_error'] = $error;
            Mage::logException($e);
            Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $error);
            return;
            //die('Error: ' . $e->getMessage());
        }

        $this->afterPlaceOrder();

        $redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();
       // echo $redirectUrl;
		
		
        if($redirectUrl)    {
            $redirect = $redirectUrl;
        } else {
        	
            $this->getOnepage()->getQuote()->setIsActive(false);
            $this->getOnepage()->getQuote()->save();
            //$redirect = $this->getUrl('checkout/onepage/success');
           $redirect = $this->getUrl('bedankt');
            //$this->_redirect('checkout/onepage/success', array('_secure'=>true));
        }
       
        Header('Location: ' . $redirect);
        exit();
    }

    
}
