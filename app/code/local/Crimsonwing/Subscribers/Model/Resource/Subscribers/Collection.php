<?php

class Crimsonwing_Subscribers_Model_Resource_Subscribers_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

    protected function _construct()
    {
        $this->_init('subscribers/subscribers');
    }
}