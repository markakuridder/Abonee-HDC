<?php

class Crimsonwing_Subscribers_Model_Resource_Subscribers extends Mage_Core_Model_Resource_Db_Abstract
{

    protected function _construct()
    {
        $this->_init('subscribers/subscribers', 'id');
    }

}
