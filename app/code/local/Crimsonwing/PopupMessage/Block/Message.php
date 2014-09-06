<?php

class Crimsonwing_PopupMessage_Block_Message extends Mage_Core_Block_Template
{

    public function getMessage()
    {
        if (!empty($_GET['message_id'])) {
            // for testing and styling purposes
            Mage::getSingleton('popupmessage/message')->setMessageStaticBlockId($_GET['message_id']);
        }
        return trim(Mage::getSingleton('popupmessage/message')->getMessage());
    }


    public function markMessageRead()
    {
        Mage::getSingleton('popupmessage/message')->clearMessage();
    }

}