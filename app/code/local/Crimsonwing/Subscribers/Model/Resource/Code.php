<?php

class Crimsonwing_Subscribers_Model_Resource_Code extends Mage_Core_Model_Resource_Db_Abstract
{

    protected function _construct()
    {
        $this->_init('subscribers/code', 'code_id');
    }


    public function getFirstAvailableId()
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('subscribers/code'), 'code_id')
            ->where('assigned_at IS NULL')
            ->order('code_id ASC')
            ->limit(1)
        ;

        $id = $this->_getReadAdapter()->fetchOne($select);
        if (!$id) {
            Mage::throwException(Mage::helper('subscribers')->__('There are no available codes left.'));
        }

        return $id;
    }


    public function generateCodes()
    {
        $count = 0;
        for ($i = 0; $i < 1000; $i++) {
            $code = strtoupper(substr(sha1(rand() . time()), 0, 5));
            $select = $this->_getReadAdapter()->select()
                ->from($this->getTable('subscribers/code'), 'code_id')
                ->where('code = ?', $code);
            if ($this->_getReadAdapter()->fetchOne($select)) {
                continue;
            }
            $count++;
            Mage::getModel('subscribers/code')->setCreatedAt(Mage::getSingleton('core/date')->gmtDate())->setCode($code)->save();
        }
        return $count;
    }

}
