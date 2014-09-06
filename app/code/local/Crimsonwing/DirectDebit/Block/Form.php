<?php

/**
 * Crimsonwing DirectDebit form block.
 *
 * @category Crimsonwing_DirectDebit
 * @author Maurice Faber <maurice.faber@vivendo.nl>
 *
 */
class Crimsonwing_DirectDebit_Block_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('directdebit/form.phtml');
    }

    public function getCustomerBankaccount()
    {
        $value = $this->getMethod()->getInfoInstance()->customerBankaccount;
        if (!$value) {
            $value = Mage::getSingleton('customer/session')->customerBankaccount;
        }
        return $value;
    }
}
