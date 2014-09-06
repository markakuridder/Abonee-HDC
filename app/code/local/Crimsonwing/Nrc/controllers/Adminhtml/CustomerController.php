<?php
include_once("Mage/Adminhtml/controllers/CustomerController.php");
class Crimsonwing_Nrc_Adminhtml_CustomerController extends Mage_Adminhtml_CustomerController
{
	
	public function sendemailAction()
	{
		$customersIds = $this->getRequest()->getParam('customer');
        if(!is_array($customersIds)) {
             Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select customer(s).'));

        } else {
            try {
                foreach ($customersIds as $customerId) {
                    $customer = Mage::getModel('customer/customer')->load($customerId);
                    $password = $customer->generatePassword();
                    $customer->setPassword($password);
                    $customer->save();
                    $customer->sendNewAccountEmail();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Email were send for %d record(s).', count($customersIds)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
	}
}