<?php

class Crimsonwing_Subscribers_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_MESSAGE_INVALID_VOUCHER_CODE = 'nrc/subscribers/invalid_voucher_code_block_id';


    public function getInvalidVoucherCodeBlockId()
    {
        return Mage::getStoreConfig(self::XML_PATH_MESSAGE_INVALID_VOUCHER_CODE);
    }

}