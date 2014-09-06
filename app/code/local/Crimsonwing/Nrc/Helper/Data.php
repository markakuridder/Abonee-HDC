<?php

class Crimsonwing_Nrc_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_MESSAGE_PREVIOUS_ORDER = 'nrc/messages/previous_order';

    public function getPreviousOrderMessageBlockId()
    {
        return Mage::getStoreConfig(self::XML_PATH_MESSAGE_PREVIOUS_ORDER);
    }
}