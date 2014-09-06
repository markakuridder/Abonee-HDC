<?php
class Crimsonwing_Nrc_Model_Observer {
	
	// getPreviousOrderMessageBlockId
	
	/**
	 * Check if there's already an order placed for this address
	 *
	 * Available in Event: controller_action
	 *
	 * @param Varien_Event_Observer $observer        	
	 * @param string $url
	 *        	The URL to redirect to if the check fails
	 * @param bool $ajax
	 *        	Must the response be an ajax response? (TRUE for onepage, FALSE for onestep)
	 * @return void
	 */
	protected function _checkPreviousOrder(Varien_Event_Observer $observer, $url, $ajax = true) {
		
		if (isset($_SERVER['SUBESH']) && $_SERVER['SUBESH'] === true) {
			return;
		}
		
		/**
		 * @var Mage_Core_Controller_Varien_Action
		 */
		$action = $observer->getEvent ()->getControllerAction ();
		
		/**
		 * @var Mage_Sales_Model_Quote
		 */
		$quote = $this->_getOnepage ()->getQuote ();
		if ($quote && $quote->getId ()) {
			$address = $quote->getBillingAddress ();
			$postcode = preg_replace ( '/[^a-z0-9]/i', '', $address->getPostcode () );
			if (preg_match ( '/([0-9]{4})([a-z]{2})/i', $postcode, $matches )) {
				$searchPostcode = $matches [1] . '%' . $matches [2];
			} else {
				$searchPostcode = $postcode;
			}
			
			$addresses = Mage::getModel ( 'sales/order_address' )->getCollection ()->addFieldToFilter ( 'postcode', array (
					'like' => $searchPostcode 
			) );
			
			foreach ( $addresses as $previousAddress ) {
				if (trim ( strtoupper ( $previousAddress->getStreet2 () ) ) != trim ( strtoupper ( $address->getStreet2 () ) )) {
					continue;
				}
				if (trim ( strtoupper ( $previousAddress->getStreet3 () ) ) != trim ( strtoupper ( $address->getStreet3 () ) )) {
					continue;
				}
				if ($orderId = $previousAddress->getParentId ()) {
					if ($order = Mage::getModel ( 'sales/order' )->load ( $orderId )) {
						if ($order->getId ()) {
							if ($order->getState () != Mage_Sales_Model_Order::STATE_CANCELED && $order->getState () != Mage_Sales_Model_Order::STATE_CLOSED) {
								Mage::getSingleton ( 'popupmessage/message' )->setMessageStaticBlockId ( $this->_getHelper ()->getPreviousOrderMessageBlockId () );
								if ($ajax) {
									$action->getResponse ()->setBody ( Mage::helper ( 'core' )->jsonEncode ( array (
											'redirect' => $url 
									) ) )->sendResponse ();
								} else {
									$action->getResponse ()->setRedirect ( $url )->sendResponse ();
								}
								exit ();
							}
						}
					}
				}
			}
		}
	}
	
	/**
	 * Check if there's an active subscription on the address (Onepage Checkout)
	 *
	 * @see _checkPreviousOrder()
	 * @param Varien_Event_Observer $observer        	
	 * @return void
	 */
	public function checkPreviousOrderOnepage(Varien_Event_Observer $observer) {
		
		try {
			if (isset($_SERVER['SUBESH']) && $_SERVER['SUBESH'] === true) {
				return;
			}
			
			$url = Mage::getUrl ( 'checkout/onepage' );
			
			$this->_checkPreviousOrder ( $observer, $url, true );
			
			if ($this->_isStreetStore()) { // For street, only check Magento Order not in subscription table.
				return;
			}
			
			// Additionally check in subscription table which are imported.
			$this->_checkInSubscriptionTable ( $observer, $url );
		} catch ( Exception $e ) {
			$m = $e->getMessage ();
		}
	}
	
	/**
	 * @author Subesh
	 * @param Varien_Event_Observer $observer
	 * @param unknown $url
	 */
	protected function _checkInSubscriptionTable(Varien_Event_Observer $observer, $url) {
		
		if (isset($_SERVER['SUBESH']) && $_SERVER['SUBESH'] === true) {
			return;
		}
		
		if ($this->_isStreetStore()) { // For street, only check Magento Order not in subscription table.
			return;
		}
		
		
		/**
		 * @var Mage_Core_Controller_Varien_Action
		 */
		$action = $observer->getEvent ()->getControllerAction ();
		
		$quote = $this->_getOnepage ()->getQuote ();
		if ($quote && $quote->getId ()) {
			$address = $quote->getBillingAddress ();
			$postcode = preg_replace ( '/[^a-z0-9]/i', '', $address->getPostcode () );
			if (preg_match ( '/([0-9]{4})([a-z]{2})/i', $postcode, $matches )) {
				$searchPostcode = $matches [1] . '%' . $matches [2];
			} else {
				$searchPostcode = $postcode;
			}
			
			$houseNr = trim($quote->getBillingAddress ()->getStreet2 ());
			$ext = trim($quote->getBillingAddress()->getStreet3 ());
			$_resource = Mage::getSingleton ( 'core/resource' );
			$read = $_resource->getConnection ( 'core_read' );
			
			$result = $read->fetchRow ( "SELECT * FROM {$_resource->getTableName('subscriptions')} WHERE postcode like '{$searchPostcode}' AND houseno='{$houseNr}' AND houseno_ext = '{$ext}';" );
			
			if ($result) {
				Mage::log('IN Susbcriptions');
				Mage::getSingleton ( 'popupmessage/message' )->setMessageStaticBlockId ('message_already_subscribed');
				$action->getResponse ()->setRedirect ( $url )->sendResponse ();
				exit ();
				// return $this->_returnJSON(array('status' => 'error', 'data' => array('code' => 'subscription_exists', 'message' => Mage::helper('rbncha_affiliate')->__('A subscription already exists in this address'))));
			}
		}
	}
	
	/**
	 * Check if there's an active subscription on the address (Onepage Checkout)
	 *
	 * @see _checkPreviousOrder()
	 * @param Varien_Event_Observer $observer        	
	 * @return void
	 */
	public function checkPreviousOrderOnestep(Varien_Event_Observer $observer) {
// 		if ($this->_isStreetStore()) {
// 			return;
// 		}
		/**
		 * @var Mage_Core_Controller_Varien_Action
		 */
		$action = $observer->getEvent ()->getControllerAction ();
		if (strtoupper ( $action->getRequest ()->getMethod () ) == 'POST') {
			$url = Mage::getUrl ( 'onestepcheckout' );
			$this->_checkPreviousOrder ( $observer, $url, false );
			
			// Additionally check in subscription table which are imported.
			$this->_checkInSubscriptionTable ( $observer, $url );
		}
	}
	
	protected function _isStreetStore()
	{
		return false;
	}
	
	/**
	 * Check if the age of the customer is at least 18
	 *
	 * @param Varien_Event_Observer $observer        	
	 * @param string $url
	 *        	The URL to redirect to if the check fails
	 * @param bool $ajax
	 *        	Must the response be an ajax response? (TRUE for onepage, FALSE for onestep)
	 * @return void
	 */
	protected function _checkBirthDate(Varien_Event_Observer $observer, $url, $ajax) {
		
		/**
		 * @var Mage_Core_Controller_Varien_Action
		 */
		$action = $observer->getEvent ()->getControllerAction ();
		
		/**
		 * @var Mage_Sales_Model_Quote
		 */
		$quote = $this->_getOnepage ()->getQuote ();
		if ($quote->getCustomerDob ()) {
			if (strtotime ( $quote->getCustomerDob () . ' + 18 years' ) > time ()) {
				Mage::getSingleton ( 'core/session' )->addError ( Mage::helper ( 'nrc' )->__ ( 'You must be at least 18 years old.' ) );
				if ($ajax) {
					$action->getResponse ()->setBody ( Mage::helper ( 'core' )->jsonEncode ( array (
							'redirect' => $url 
					) ) )->sendResponse ();
				} else {
					$action->getResponse ()->setRedirect ( $url )->sendResponse ();
				}
				exit ();
			}
		}
	}
	
	/**
	 * Check if the age of the customer is at least 18 (OneStepCheckout)
	 *
	 * @param Varien_Event_Observer $observer        	
	 */
	public function checkBirthDateOnestep(Varien_Event_Observer $observer) {
		
		if ($this->_isStreetStore()) {
			return;
		}
		/**
		 * @var Mage_Core_Controller_Varien_Action
		 */
		$action = $observer->getEvent ()->getControllerAction ();
		if (strtoupper ( $action->getRequest ()->getMethod () ) == 'POST') {
			$url = Mage::getUrl ( 'onestepcheckout' );
			$this->_checkBirthDate ( $observer, $url, false );
		}
	}
	
	/**
	 * Check if the age of the customer is at least 18 (Onepage Checkout)
	 *
	 * @param Varien_Event_Observer $observer        	
	 */
	public function checkBirthDateOnepage(Varien_Event_Observer $observer) {
		if ($this->_isStreetStore()) {
			return;
		}
		$url = Mage::getUrl ( 'checkout/onepage' );
		$this->_checkBirthDate ( $observer, $url, true );
	}
	
	/**
	 * Get one page checkout model
	 *
	 * @return Mage_Checkout_Model_Type_Onepage
	 */
	protected function _getOnepage() {
		return Mage::getSingleton ( 'checkout/type_onepage' );
	}
	
	/**
	 * Get helper
	 *
	 * @return Crimsonwing_Nrc_Helper_Data
	 */
	protected function _getHelper() {
		return Mage::helper ( 'nrc' );
	}
}