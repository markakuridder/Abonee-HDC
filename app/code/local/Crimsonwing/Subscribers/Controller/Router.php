<?php

class Crimsonwing_Subscribers_Controller_Router extends Mage_Core_Controller_Varien_Router_Abstract
{
    const FRONT_NAME = 'abonnee';
    const TARGET_NAME = 'info';

    public function initControllerRouters($observer)
    {
        $observer->getEvent()->getFront()->addRouter(self::FRONT_NAME, $this);
    }

    public function match(Zend_Controller_Request_Http $request)
    {
    	$urlParts = explode('/', $request->getPathInfo());

        $params = array();
        if (isset($urlParts[1]) && in_array($urlParts[1], array('voucher', 'abonnee'))) {
            $action = '';
            if (isset($urlParts[2])) {
                $action = $urlParts[2];
            }
            switch ($urlParts[1]) {
                case 'abonnee':
                    //ajax-pagina
                    $request->setModuleName('abonnee')
                        ->setControllerName('abonnee');
                    if ($action) {
                        $request->setActionName($action);
                    }
                    return true;
                case 'voucher':
                    //normale pagina
                    $request->setModuleName('abonnee')
                        ->setControllerName('voucher');
                    if ($action) {
                        $request->setActionName($action);
                    }
                    return true;
            }
        }
        return false;
    }
}