<?php

/**
 * Crimsonwing DirectDebit payment model.
 *
 * @category Crimsonwing_DirectDebit
 * @author Maurice Faber <maurice.faber@vivendo.nl>
 *
 */
class Crimsonwing_DirectDebit_Model_DirectDebit extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'directdebit';
    protected $_formBlockType = 'directdebit/form';
    protected $_infoBlockType = 'directdebit/info';
	
 	/**
     * Check whether method is available
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return bool
     */
    
    public function isAvailable($quote = null)
    {        
    	return parent::isAvailable($quote) && (!empty($quote));
    }
    
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info = $this->getInfoInstance();
        $info->customerBankaccount = $data->customerBankaccount;
        return $this;
    }

    public function validate()
    {
       // exit();
    	parent::validate();
        $paymentInfo = $this->getInfoInstance();
        // validate if we have our values
        if (!$paymentInfo->customerBankaccount) {
            Mage::throwException(Mage::helper('directdebit')->__('Bank Account Number is required.'));
        }
        
        // Check for periods in Number
	    if ( preg_match('/[\\s\\,\\.\\-]/', $paymentInfo->customerBankaccount)) {
	  		Mage::throwException(Mage::helper('directdebit')->__('Bank Account Number should not contain space or periods.'));
	    }
        
        $validator = new Crimsonwing_Validate_Bankaccount();
        if (!$validator->isValid($paymentInfo->customerBankaccount)) {
            Mage::throwException(Mage::helper('directdebit')->__('Bank Account Number is not valid.'));
        }
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        // keep data in session
        Mage::getSingleton('customer/session')->customerBankaccount = $paymentInfo->customerBankaccount;
        return $this;
    }

    public function getNotice()
    {
        return $this->getConfigData('notice');
    }
}
