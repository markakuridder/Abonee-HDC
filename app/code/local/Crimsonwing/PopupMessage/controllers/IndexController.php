<?php

class Crimsonwing_Zeno_IndexController extends Mage_Core_Controller_Front_Action
{

    protected function testAction()
    {
        /*
        if (!$this->getRequest()->getParam('postcode')) {
            echo '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">';
            echo 'Postcode:<input type="text" name="postcode"><br />';
            echo 'Huisnummer:<input type="text" name="huisnummer"><br />';
            echo '<button type="submit">Opzoeken</button>';
            echo '</form>';
        } else {
            $postcode = $this->getRequest()->getParam('postcode');
            $houseNumber = $this->getRequest()->getParam('huisnummer');
            //$info = Mage::getModel('zeno/api')->hasActiveSubscription($this->getRequest()->getParam('postcode'), $this->getRequest()->getParam('huisnummer'));
            //$info = Mage::getModel('zeno/api')->getSubscribers($this->getRequest()->getParam('postcode'), $this->getRequest()->getParam('huisnummer'), '');
            //$info = Mage::getModel('zeno/api')->checkDelivery($postcode, $houseNumber, 'NRC');
            $info = Mage::getModel('zeno/api')->getAddressInformation($postcode, $houseNumber);
            echo 'postcode: ' . $this->getRequest()->getParam('postcode') . '<br />';
            echo 'huisnummer: ' . $this->getRequest()->getParam('huisnummer') . '<br />';
            print_r($info);
        }
        */
        //print_r(Mage::getModel('focum/api')->test());
    }


    public function addressLookupAction()
    {
        if (!$this->_validateFormKey()) {
            return $this->_returnJSON(array('status' => 'error', 'data' => array('code' => 'invalid_form_key', 'message' => 'Form key not validated')));
        }

        $postcode = strtoupper($this->getRequest()->getParam('postcode'));
        $houseNumber = $this->getRequest()->getParam('house_number');

        /** @var Crimsonwing_Zeno_Model_Api */
        $api = Mage::getModel('zeno/api');

        try {
            $address = $api->getAddressInformation($postcode, $houseNumber);

            if ($address && $address->hasData()) {
                $data = $address->getData();
                return $this->_returnJSON(array('status' => 'ok', 'data' => $data));
            } else {
                return $this->_returnJSON(array('status' => 'error', 'data' => array('code' => 'address_not_found', 'message' => Mage::helper('zeno')->__('Het adres is niet gevonden. Controleer postcode en huisnummer alstublieft op tikfouten.'))));
            }
        } catch (Exception $e) {
            return $this->_returnJSON(array('status' => 'error', 'data' => array('code' => 'could_not_lookup_postcode', 'message' => 'Could not lookup postcode')));
        }
    }


    protected function _returnJSON($result)
    {
        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody(json_encode($result));
        $this->getResponse()->sendResponse();
        exit;
    }
}