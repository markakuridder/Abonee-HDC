<?php

/**
* Message Model
*
* Supposed to be used as singleton
*/
class Crimsonwing_PopupMessage_Model_Message
{

    /**
    * Set a message string (html allowed)
    *
    * @param string $message
    * @return Crimsonwing_PopupMessage_Model_Message
    */
    public function setMessage($message)
    {
        Mage::getSingleton('core/session')->setPopupMessage($message);
        Mage::getSingleton('core/session')->unsPopupMessageBlockId();
        return $this;
    }

    /**
    * Set a message by specifying the static block id
    *
    * @param string $blockId
    * @return Crimsonwing_PopupMessage_Model_Message
    */
    public function setMessageStaticBlockId($blockId)
    {
        if (!$blockId) {
            Mage::getSingleton('core/session')->addError('Cannot set message block');
        }
        Mage::getSingleton('core/session')->unsPopupMessage();
        Mage::getSingleton('core/session')->setPopupMessageBlockId($blockId);
        return $this;
    }


    /**
    * Get the message previously set
    *
    * @return string
    */
    public function getMessage()
    {
        if ($message = Mage::getSingleton('core/session')->getPopupMessage()) {
            return $message;
        } elseif ($blockId = Mage::getSingleton('core/session')->getPopupMessageBlockId()) {
            return Mage::app()->getLayout()->createBlock('cms/block')->setBlockId($blockId)->toHtml();
        }
        return '';
    }


    /**
    * Clear the message in the session
    *
    * @return Crimsonwing_PopupMessage_Model_Message
    */
    public function clearMessage()
    {
         Mage::getSingleton('core/session')->unsPopupMessage();
         Mage::getSingleton('core/session')->unsPopupMessageBlockId();
         return $this;
    }

}