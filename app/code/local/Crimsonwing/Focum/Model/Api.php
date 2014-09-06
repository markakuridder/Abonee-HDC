<?php

class Crimsonwing_Focum_Model_Api
{
    const XML_PATH_CONNECTION_MODE = 'focum/connection/mode';
    const XML_PATH_CONNECTION_URL_TEST = 'focum/connection/url_test';
    const XML_PATH_CONNECTION_USERNAME_TEST = 'focum/connection/username_test';
    const XML_PATH_CONNECTION_PASSWORD_TEST = 'focum/connection/password_test';
    const XML_PATH_CONNECTION_URL_LIVE = 'focum/connection/url_live';
    const XML_PATH_CONNECTION_USERNAME_LIVE = 'focum/connection/username_live';
    const XML_PATH_CONNECTION_PASSWORD_LIVE = 'focum/connection/password_live';

    protected $_connection;
    protected $_logCalls = true;


    /**
    * Create a connection to the SOAP server
    *
    * @return SoapClient
    */
    protected function _getConnection()
    {
        if (!$this->_connection) {
            $options = array();
            if (Mage::getStoreConfig(self::XML_PATH_CONNECTION_MODE) == 'live') {
                $url = Mage::getStoreConfig(self::XML_PATH_CONNECTION_URL_LIVE);
                $options = array(
                    'trace' => 1,
                );
            } else {
                $url = Mage::getStoreConfig(self::XML_PATH_CONNECTION_URL_TEST);
                $options = array(
                    'trace' => 1,
                );
            }

            $this->_connection = new SoapClient($url, array_merge(array(
                'connection_timeout' => 15,
            ), $options));
        }

        return $this->_connection;
    }


    /**
    * Get the username from settings, based on setting for 'mode'
    *
    * @return string Username
    */
    protected function _getUsername()
    {
        if (Mage::getStoreConfig(self::XML_PATH_CONNECTION_MODE) == 'live') {
            $username = Mage::getStoreConfig(self::XML_PATH_CONNECTION_USERNAME_LIVE);
        } else {
            $username = Mage::getStoreConfig(self::XML_PATH_CONNECTION_USERNAME_TEST);
        }
        return $username;
    }


    /**
    * Get the password from settings, based on setting for 'mode'
    *
    * @return string Password
    */
    protected function _getPassword()
    {
        if (Mage::getStoreConfig(self::XML_PATH_CONNECTION_MODE) == 'live') {
            $password = Mage::getStoreConfig(self::XML_PATH_CONNECTION_PASSWORD_LIVE);
        } else {
            $password = Mage::getStoreConfig(self::XML_PATH_CONNECTION_PASSWORD_TEST);
        }
        return $password;
    }


    public function test()
    {
        $connection = new SoapClient('https://acc.riskportal.nl/portal.focum.wsdl', array(
            'connection_timeout' => 15,
            'trace' => 1,
        ));

        try {
            //$result = $connection->doCheck('acc_projaxion01', 'kXvyipec8', 'RiskSecure');
            $result = $connection->__soapCall('doCheck', array(
                'username' => 'acc_projaxion01',
                'password' => 'kXvyipec8',
                'product'  => 'RiskSecure',
                'request'  => array(
                    'name' => 'Mark',
                    'sexe' => 'M',
                    'initials' => 'MTP',
                    'prefix' => 'van der',
                    'lastname' => 'sanden',
                    'birthdate' => '1977-06-08',
                    'street' => 'zebraspoor',
                    'housenumber' => '747',
                    'zipcode' => '3605HS',
                    'city' => 'Maarssen',
                    'telefoonnummer' => '0346550210',
                ),
            ));
        } catch (Exception $e) {
            echo 'ERROR: ' . $e->getMessage();
            echo '<br />RESPONSE: <br />';
            echo nl2br(htmlentities($connection->__getLastResponse()));
        }

        print_r($result);
            echo '<br />RESPONSE: <br />';
            echo nl2br(htmlentities($connection->__getLastResponse()));
            echo '<br /><br />REQUEST: <br />';
            echo nl2br(htmlentities($connection->__getLastRequest()));

    }


    /**
    * Do the credit check
    *
    * Fields for data:
    * - gender (F or M - required, but can be empty)
    * - firstname (initials)
    * - lastname
    * - email
    * - street1
    * - street2 (house number)
    * - postcode (nnnnAA)
    * - city
    * - dob (yyyy-mm-dd)
    * - telephone (required, but can be empty)
    *
    * Optional:
    * - middlename
    * - street3 (extension)
    *
    * @param array $data
    * @return string Code returned from Focum (R00 - Rxx). Recommended is to reject < R02
    */
    public function creditCheck($data)
    {
        if ($this->_logCalls) {
            Mage::log('DATA: ' . json_encode($data), 7, 'focum.log');
        }
        // first, check the data
        $requiredData = array(
            'gender' => false, 'firstname' => true, 'lastname' => true, 'email' => true, 'telephone' => false, 'email' => true,
            'street1' => true, 'street2' => true, 'city' => true, 'postcode' => '/^[0-9]{4}\s*[a-z]{2}$/i',
            'dob' => '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',
        );
        foreach ($requiredData as $key => $check) {
            if (!isset($data[$key])) {
                Mage::throwException(Mage::helper('focum')->__('Missing value: %s'), $key);
            }
            $value = $data[$key];
            if ($check === true) {
                if (!trim($value)) {
                    Mage::throwException(Mage::helper('focum')->__('Field should have a value: %s', $key));
                }
            } elseif (is_string($check)) {
                if (!preg_match($check, $value)) {
                    Mage::throwException(Mage::helper('focum')->__('Field doesn\'t match requirement: %s', $key));
                }
            }
        }

        // now call the webservice
        $requestData = array(
            'initials'      => $data['firstname'],
            'prefix'        => (isset($data['middlename']) ? $data['middlename'] : ''),
            'lastname'      => $data['lastname'],
            'birthdate'     => $data['dob'],
            'street'        => $data['street1'],
            'housenumber'   => $data['street2'],
            'extension'     => (isset($data['street3']) ? $data['street3'] : ''),
            'emailaddress'  => $data['email'],
            'zipcode'       => $data['postcode'],
            'city'          => $data['city'],
            'phonenumber'   => $data['telephone'],
        );
        if ($data['gender'] == 'F') {
            $requestData['sexe'] = 'F';
        } elseif ($data['gender'] == 'M') {
            $requestData['sexe'] = 'M';
        }
        
        $paymentInfo = Mage::getSingleton('checkout/session')->getQuote()
        ->getPayment()->getMethodInstance()->getInfoInstance()->customerBankaccount;
        $isbnValidator = new Crimsonwing_Validate_Isbn();
        $isbnCheck = $isbnValidator->iban_controle($paymentInfo);
        if ($isbnCheck) {
        	$requestData ['iban'] = $paymentInfo;
        } else {
        	$requestData ['accountnumber'] = $paymentInfo;
        }

        if ($this->_logCalls) {
            Mage::log('REQUEST DATA: ' . json_encode($requestData), 7, 'focum.log');
        }
        // FOR DEBUGGING:
        //return 'R01';

        $connection = $this->_getConnection();
        try {
            $result = $connection->__soapCall('doCheck', array(
                'username' => $this->_getUsername(),
                'password' => $this->_getPassword(),
                'product'  => 'RiskSecure',
                'request'  => $requestData,
            ));
            if ($this->_logCalls) {
                Mage::log('REQUEST: ' . preg_replace('/[\r\n]+/', '', $connection->__getLastRequest()), 7, 'focum.log');
                Mage::log('RESPONSE: ' . preg_replace('/[\r\n]+/', '', $connection->__getLastResponse()), 7, 'focum.log');
            }

            if ($result->risk && $result->risk->result) {
                return $result->risk->result;
            }
            Mage::throwException(Mage::helper('focum')->__('There was no answer from the web service'));
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::log('ERROR: ' . $e->getMessage());
            Mage::throwException('Something went wrong while making API request: ' . $e->getMessage());
        }
    }


    /**
    * Check whether delivery is available on the specified address
    *
    * @param string $postcode
    * @param string $houseNumber
    * @param string $uitgaveCode  Specify one of $uitgaveCode or $pakketCode
    * @param string $pakketCode   Specify one of $uitgaveCode or $pakketCode
    * @return bool TURE if delivery is available, FALSE if not
    */
    public function checkDelivery($postcode, $houseNumber, $uitgaveCode = '', $pakketCode = '')
    {
        return false;
        $connection = $this->_getConnection();

        try {
            $postcode = strtoupper(preg_replace('/[^a-z0-9]/i', '', $postcode));
            $houseNumber = trim($houseNumber);

            $result = $connection->wijkCheck(array(
                'callerName'   => Mage::getStoreConfig(self::XML_PATH_CONNECTION_CALLER_NAME),
                'requestTag'   => __FUNCTION__,
                'postcode'     => $postcode,
                'huisNummer'   => $houseNumber,
                'uitgaveCode'  => ($uitgaveCode) ? $uitgaveCode : '',
                'aboSoortCode' => '',
                'pakketCode'   => ($pakketCode) ? $pakketCode : '',
            ));
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::throwException('Something went wrong while making API request: ' . $e->getMessage());
        }

        if ($result->return && $result->return->leveringMogelijk) {
            return true;
        }

        return false;
    }

}