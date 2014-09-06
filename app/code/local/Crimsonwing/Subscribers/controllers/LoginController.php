<?php
require_once ('Mage/Customer/controllers/AccountController.php');
class Crimsonwing_Subscribers_LoginController extends Mage_Customer_AccountController {
	
	/**
	 * Retrieve customer session model object
	 *
	 * @return Mage_Customer_Model_Session
	 */
	protected function _getSession()
	{
		return Mage::getSingleton('customer/session');
	}
	
	/**
	 * Action predispatch
	 *
	 * Check customer authentication for some actions
	 */
	public function preDispatch() {
	}
	
	/**
	 * Handles action from Email auto login URL
	 */
	public function autheticateAction() {
		
		$user = base64_decode ( $this->_request->getParam ( 'u' ) ); // user Email was encoded when sending email
		$pass = $this->_request->getParam ( 'p' );
		

		$form = new Varien_Data_Form();
		$form->setAction(Mage::getUrl('customer/account/loginpost'))
		->setId('auto_login')
		->setName('auto_login')
		->setMethod('POST')
		->setUseContainer(true);
		
		$form->addField('username', 'hidden', array('name'=>'login[username]', 'value'=>$user));
		$form->addField('password', 'hidden', array('name'=>'login[password]', 'value'=>$pass));
		
		$html = '<html><body>';
		$html.= $this->__('Please wait...');
		$html.= $form->toHtml();
		$html.= '<script type="text/javascript">document.getElementById("auto_login").submit();</script>';
		$html.= '</body></html>';
		echo $html;
		exit;
		
	}
	
}
