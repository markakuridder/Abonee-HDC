<?php
/**
 * DirectDebit Observer model
 *
 * @category   Crimsonwing
 * @package    Crimsonwing_DirectDebit
 * @author     M. Faber <morriz@idiotz.nl>
 */
class Crimsonwing_DirectDebit_Model_Observer
{
    /**
     * Register our custom namespace with the Zend Autoloader.
     * Called upon controller_front_init_before
     *
     * @param Varien_Event_Observer $observer
     */
    public function loadCustomNameSpace(Varien_Event_Observer $observer)
    {
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('Crimsonwing');
        return $this;
    }

}
