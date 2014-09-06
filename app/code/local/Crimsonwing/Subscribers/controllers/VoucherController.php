<?php

class Crimsonwing_Subscribers_VoucherController extends Mage_Core_Controller_Front_Action
{
    const XML_PATH_EMAIL_TEMPLATE = 'nrc/subscribers/email_template';
    const XML_PATH_EMAIL_SENDER = 'nrc/subscribers/sender_email_identity';

    public function indexAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages(array('core/session', 'customer/session'));
        $messages=Mage::getSingleton("customer/session")->getMessages();
        $this->getLayout()->getBlock('voucher.form')
            ->setFormAction( Mage::getUrl('*/*/post') );
        return $this->renderLayout();
    }


    public function postAction()
    {
        $post = $this->getRequest()->getPost();
        if ($post) {
            $translate = Mage::getSingleton('core/translate');
            $translate->setTranslateInline(false);

            try {
                $error = false;
                if (!Zend_Validate::is(trim($post['subscriber_number']) , 'NotEmpty')) {
                    $error = true;
                }
                if (!Zend_Validate::is(trim($post['subscriber_name']) , 'NotEmpty')) {
                    $error = true;
                }
                if (!Zend_Validate::is(trim($post['subscriber_email']), 'EmailAddress')) {
                    $error = true;
                }
                if ($error) {
                    throw new Exception();
                }

                // now reserve the number
                /** @var Crimsonwing_Subscribers_Model_Code */
                $newCode = Mage::getModel('subscribers/code')->loadFirstAvailable()
                    ->assign()
                    ->setSubscriberNumber($post['subscriber_number'])
                    ->setSubscriberName($post['subscriber_name'])
                    ->setSubscriberEmail($post['subscriber_email'])
                    ->save() // now the number is reserved
                ;

                $this->_createShoppingCartPriceRule($newCode);

                $mailTemplate = Mage::getModel('core/email_template');
                /* @var $mailTemplate Mage_Core_Model_Email_Template */
                $mailTemplate->setDesignConfig(array('area' => 'frontend'))
                    ->sendTransactional(
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER),
                        $newCode->getSubscriberEmail(),
                        null,
                        array('code' => $newCode)
                    );


                $translate->setTranslateInline(true);
                Mage::getSingleton('customer/session')->addSuccess(sprintf(Mage::helper('subscribers')->__('The assigned code is %s and it has been emailed to %s on the email address %s.'), $newCode->getCode(), $newCode->getSubscriberName(), $newCode->getSubscriberEmail()));
                $this->_redirect('*/*/');

                return;
            } catch (Exception $e) {
                $translate->setTranslateInline(true);

                Mage::getSingleton('customer/session')->addError($e->getMessage());
                $this->_redirect('*/*/');
                return;
            }
        } else {
            $this->_redirect('*/*/');
        }
    }


    protected function _createShoppingCartPriceRule($code)
    {
        $shoppingCartPriceRule = Mage::getModel('salesrule/rule')
            ->setName('Code voor ' . $code->getSubscriberName() . ' (' . $code->getSubscriberNumber() . ')')
            ->setDescription('Gemaild aan e-mailadres ' . $code->getSubscriberEmail())
            ->setFromDate(date('Y-m-d', strtotime("yesterday")))
            ->setCouponType(Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC)
            ->setCouponCode($code->getCode())
            ->setUsesPerCoupon(1)
            ->setUsesPerCustomer(1)
            ->setCustomerGroupIds($this->_getAllCustomerGroupIds())
            ->setIsActive(1)
            ->setStopRulesProcessing(0)
            ->setIsAdvanced(1)
            ->setProductIds('')
            ->setSortOrder(0)
            ->setSimpleAction(Mage_SalesRule_Model_Rule::CART_FIXED_ACTION)
            ->setDiscountAmount(0) // no discount
            ->setDiscountQty("1")
            ->setDiscountStep('0')
            ->setSimpleFreeShipping('0')
            ->setApplyToShipping('0')
            ->setIsRss(0)
            ->setWebsiteIds(Mage::app()->getDefaultStoreView()->getWebsiteId())
            ->save();
    }


    protected function _getAllCustomerGroupIds()
    {
        $customerGroupIds = Mage::helper('customer')->getGroups()->getAllIds();
        array_unshift($customerGroupIds, "0");
        return $customerGroupIds;
    }


    public function generateCodesAction()
    {
        die('Codes generated: ' . Mage::getModel('subscribers/code')->generateCodes());
    }


    public function importCodesAction()
    {
        echo '<h1>Import codes</h1>';

        if (!$this->getRequest()->getPost()) {
            echo '<form action="' . htmlentities(Mage::getUrl('*/*/*')) . '" method="post">';
            echo 'File: <select name="file">';
             $files = glob('/var/www/html/ipad.nrc.nl/var/import/voucher.csv');
foreach ($files as $file) {
                echo '<option value="' . htmlentities(basename($file)) . '">' . htmlentities(basename($file)) . '</option>';
            }
            echo '</select><br />';
            echo '<button type="submit">Submit</button>';
            echo '</form>';
        } else {
            echo $filename = '/var/www/html/ipad.nrc.nl/var/import/voucher.csv';
            $fp = fopen($filename, 'r');
            if (!$fp) {
                die('File doesn\'t exist');
            }
            $created = 0;
            $title = 0;
            while (!feof($fp)) {
                $line = fgetcsv($fp);
                $couponCode = trim($line[0]);
                if ($couponCode) {
                    $code = Mage::getModel('subscribers/code')->setCreatedAt(Mage::getSingleton('core/date')->gmtDate())->setCode($couponCode)->save();
                    $created++;
                }
                if ($tilt++ > 10000) {
                    echo 'Tilt<br />';
                    break;
                }
            }
            fclose($fp);
            echo 'Successfully created ' . $created . ' codes.<br />';
        }
    }


    public function clearAllCodesAction()
    {
        echo '<h1>Clear all codes</h1>';
        if (!$this->getRequest()->getPost('sure')) {
            echo '<form action="' . htmlentities(Mage::getUrl('*/*/*')) . '" method="post">';
            echo '<input type="checkbox" name="sure" value="1" id="xx"><label for="xx">Yes, I\'m sure I want to DELETE ALL CODES</label><br />';
            echo '<button type="submit">Submit</button>';
            echo '</form>';
        } else {
            $codes = Mage::getResourceModel('subscribers/code_collection');
            $deleted = 0;
            foreach ($codes as $code) {
                $code->delete();
                $deleted++;
            }
            echo 'Successfully deleted ' . $deleted . ' codes.<br />';
        }
    }

}
