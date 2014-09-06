<?php

class Crimsonwing_Subscribers_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action
{

    public function exportReportAction()
    {
        $collection = Mage::getResourceModel('subscribers/code_collection')
            ->addUsedFilter();

        foreach ($collection as $code) {
            $coupon = Mage::getModel('salesrule/coupon')->load($code->getCode(), 'code');
            $code->setCoupon($coupon);
        }

        $output = $this->_toCSV(array(
            'Code',
            'Verzonden op',
            'Abonneenummer',
            'E-mailadres',
            'Naam',
            'Gebruikt?'
        )) . "\r\n";
        foreach ($collection as $code) {
            $output .= $this->_toCSV(array(
                $code->getCode(),
                $code->getAssignedAt(),
                $code->getSubscriberNumber(),
                $code->getSubscriberEmail(),
                $code->getSubscriberName(),
                ($code->getCoupon()) ? $code->getCoupon()->getTimesUsed() : '',
            )) . "\r\n";
        }

        $this->_prepareDownloadResponse('vouchercodes.csv', $output, 'text/csv');
    }
    
    public function exportMapAction()
    {
    	$collection = Mage::getResourceModel('subscribers/subscribers_collection');
    	$output = $this->_toCSV(array(
    			'NAME',
    			'SKU',
    	)) . "\r\n";
    	foreach ($collection as $code) {
    		$output .= $this->_toCSV(array(
    				$code->getName(),
    				$code->getSku(),
    		)) . "\r\n";
    	}
    
    	$this->_prepareDownloadResponse('subscription_sku.csv', $output, 'text/csv');
    }


    protected function _toCSV($data, $delimiter = ';', $enclosure = '"', $escape = '"')
    {
        $values = array();
        foreach ($data as $value) {
            if (strpos($value, $enclosure) !== false || strpos($value, $delimiter) !== false) {
                $value = $enclosure . str_replace($enclosure, $escape . $enclosure, $value) . $enclosure;
            }
            $values[] = $value;
        }
        return implode($delimiter, $values);
    }


    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('promo/quote');
    }

}
