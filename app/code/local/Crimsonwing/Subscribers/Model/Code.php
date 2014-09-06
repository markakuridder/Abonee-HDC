<?php

class Crimsonwing_Subscribers_Model_Code extends Mage_Core_Model_Abstract
{

    protected function _construct()
    {
        $this->_init('subscribers/code');
    }


    /**
    * Load the first available code
    *
    * @return Crimsonwing_Subscribers_Model_Code
    */
    public function loadFirstAvailable()
    {
        return $this->load($this->getResource()->getFirstAvailableId());
    }


    /**
    * Assign the number, filling the assigned_at
    *
    * @return Crimsonwing_Subscribers_Model_Code
    */
    public function assign()
    {
        $this->setAssignedAt(Mage::getSingleton('core/date')->gmtDate());
        return $this;
    }


    /**
    * Get the resource
    *
    * @return Crimsonwing_Subscribers_Model_Resource_Code
    */
    public function getResource()
    {
        return parent::getResource();
    }


    public function generateCodes()
    {
        return $this->getResource()->generateCodes();
    }

}