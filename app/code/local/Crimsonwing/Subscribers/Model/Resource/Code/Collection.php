<?php

class Crimsonwing_Subscribers_Model_Resource_Code_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

    protected function _construct()
    {
        $this->_init('subscribers/code');
    }


    public function addUsedFilter()
    {
        $this->addFieldToFilter('assigned_at', array('gt' => 0));
        return $this;
    }

}