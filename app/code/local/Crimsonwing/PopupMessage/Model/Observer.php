<?php

class Crimsonwing_PopupMessage_Model_Observer
{

    /**
    * Apply empty layout if parameter 'popup' is given in request
    *
    * Available in event: page, controller_action
    *
    * event: cms_page_render
    * @param Varien_Event_Observer $observer
    */
    public function applyEmptyLayout(Varien_Event_Observer $observer)
    {
        $page = $observer->getEvent()->getPage();
        /** @var Mage_Cms_PageController */
        $action = $observer->getEvent()->getControllerAction();

        if ($action->getRequest()->getParam('popup')) {
            $action->getLayout()->helper('page/layout')->applyHandle('window');
        }
    }
}