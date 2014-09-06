<?php

class Crimsonwing_Focum_Model_Observer_Checkout
{
    protected $_minScore = 'R02';

    /**
    * Do the Focum risk check for the client
    *
    * Available in Event: controller_action
    *
    * event: controller_action_predispatch_checkout_onepage_saveOrder
    * @param Varien_Event_Observer $observer
    * @param bool $ajax  Must the response be an ajax response? (TRUE for onepage, FALSE for onestep)
    * @return void
    */
    public function _checkRisk(Varien_Event_Observer $observer, $urlFail, $urlCheckout, $ajax = true)
    {
        // This is already Working, but in local because of API Call this will not work
        // therefore in local just returning true.
        
    	
    	
    	if (isset($_SERVER['SUBESH']) && $_SERVER['SUBESH']) {
    		return true;
    	}
    	
    	// For Closed Store No Check required.
        /** @var Mage_Core_Controller_Varien_Action */
        $action = $observer->getEvent()->getControllerAction();

        /** @var Mage_Sales_Model_Quote */
        $quote = $this->_getOnepage()->getQuote();
        if ($quote && $quote->getId()) {
            /** @var Mage_Sales_Model_Quote_Address */
            $address = $quote->getBillingAddress();

            $data = array(
                'gender'     => '',
                'firstname'  => $address->getFirstname(),
                'middlename' => $address->getMiddlename(),
                'lastname'   => $address->getLastname(),
                'email'      => $address->getEmail(),
                'street1'    => $address->getStreet1(),
                'street2'    => $address->getStreet2(),
                'street3'    => $address->getStreet3(),
                'postcode'   => $address->getPostcode(),
                'city'       => $address->getCity(),
                'telephone'  => $address->getTelephone(),
                'dob'        => trim(substr($quote->getCustomerDob(), 0, 10)),
            );
            if ($quote->getCustomerGender() == 123) {
                $data['gender'] = 'M';
            } elseif ($quote->getCustomerGender() == 124) {
                $data['gender'] = 'F';
            }
			
			try {
                $result = $this->_getApi()->creditCheck($data);
            } catch (Exception $e) {
                Mage::logException($e);
                //Mage::getSingleton('core/session')->addError(Mage::helper('focum')->__('We couldn\'t do a credit check. Please try again later.'));
				//Mage::getSingleton('popupmessage/message')->setMessageStaticBlockId($this->_getHelper()->getPreviousOrderMessageBlockId());
                Mage::getSingleton('core/session')->addError(Mage::helper('focum')->__($e->getMessage()));
                if ($ajax) {
                    $action->getResponse()
                        ->setBody(Mage::helper('core')->jsonEncode(array('redirect' => $urlCheckout)))
                        ->sendResponse();
                } else {
                    $action->getResponse()
                        ->setRedirect($urlCheckout)
                        ->sendResponse();
                }
                exit;
            }
			
            if (!$result || $result < $this->_minScore) {
            	Mage::getSingleton('popupmessage/message')->setMessageStaticBlockId($this->_getHelper()->getCreditCheckFailedBlockId());
            	if ($ajax) {
            		$action->getResponse()
            		->setBody(Mage::helper('core')->jsonEncode(array('redirect' => $urlFail)))
            		->sendResponse();
            	} else {
            		$action->getResponse()
            		->setRedirect($urlFail)
            		->sendResponse();
            	}
            	exit;
            }
            
            
        }
    }

    /**
    * Do the Focum risk check for the client (Onepage Checkout)
    *
    * @see _checkRisk()
    * @param Varien_Event_Observer $observer
    * @return void
    */
    public function checkRiskOnepage(Varien_Event_Observer $observer)
    {
    
        $urlFail = Mage::getBaseUrl();
        $urlCheckout = Mage::getUrl('checkout/onepage');
        $this->_checkRisk($observer, $urlFail, $urlCheckout, true);
    }

    /**
    * Do the Focum risk check for the client (Onepage Checkout)
    *
    * @see _checkRisk()
    * @param Varien_Event_Observer $observer
    * @return void
    */
    public function checkRiskOnestep(Varien_Event_Observer $observer)
    {
    
        /** @var Mage_Core_Controller_Varien_Action */
        $action = $observer->getEvent()->getControllerAction();
        if (strtoupper($action->getRequest()->getMethod()) == 'POST') {
            $urlFail = Mage::getBaseUrl();
            $urlCheckout = Mage::getUrl('onestepcheckout');
            $this->_checkRisk($observer, $urlFail, $urlCheckout, false);
        }
    }

    protected function _isStreetStore()
    {
    	return false;
    }
    
    /**
    * Get helper
    *
    * @return Crimsonwing_Focum_Helper_Data
    */
    protected function _getHelper()
    {
        return Mage::helper('focum');
    }


    /**
    * Get Focum API model
    *
    * @return Crimsonwing_Focum_Model_Api
    */
    protected function _getApi()
    {
        return Mage::getSingleton('focum/api');
    }


    /**
     * Get one page checkout model
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    protected function _getOnepage()
    {
        return Mage::getSingleton('checkout/type_onepage');
    }
}