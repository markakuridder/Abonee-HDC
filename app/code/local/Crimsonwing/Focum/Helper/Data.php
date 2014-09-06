<?php

class Crimsonwing_Focum_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_MESSAGE_CREDIT_CHECK_FAILED = 'focum/messages/credit_check_failed';


    public function getCreditCheckFailedBlockId()
    {
        return Mage::getStoreConfig(self::XML_PATH_MESSAGE_CREDIT_CHECK_FAILED);
    }


}