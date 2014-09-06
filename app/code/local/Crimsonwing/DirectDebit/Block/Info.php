<?php

/**
 * Crimsonwing DirectDebit info block.
 *
 * @category Crimsonwing_DirectDebit
 * @author Maurice Faber <maurice.faber@vivendo.nl>
 *
 */
class Crimsonwing_DirectDebit_Block_Info extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('directdebit/info.phtml');
    }
}
