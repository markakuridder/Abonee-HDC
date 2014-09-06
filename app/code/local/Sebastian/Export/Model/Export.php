<?php

/**
 *
 * Copyright Sebastian Enzinger <sebastian@enzinger.de> www.sebastian-enzinger.de
 *
 * All rights reserved.
 *
 **/

class Sebastian_Export_Model_Export extends Mage_Core_Model_Abstract {

    public function _construct()
    {
        parent::_construct();
        $this->_init('export/export');
        $this->EXPORT_TYPES = Mage::helper('export')->getExportTypes();
    }

    public function export($export_type, $start, $end, $datefrom, $dateto, $messages = false, $auto = false, $storeId = null)
    {
        
    	if (!isset($this->EXPORT_TYPES[strtoupper($export_type)]) && !isset($this->EXPORT_TYPES[strtolower($export_type)])) {
            if (!$messages) return Mage::helper('export')->errorlog(Mage::helper('export')->__('Wrong export type.'));
            Mage::throwException(Mage::helper('export')->__('Wrong export type.'));
        }
		
        if ($end == 0) {
            $condition = array("from" => $start);
        } else {
            $condition = array("from" => $start, "to" => $end);
        }
        if (!empty($datefrom)) {
            $datefrom = Mage::app()->getLocale()->date($datefrom, Zend_Date::DATE_SHORT);
            $datefrom = $datefrom->toString('YYYY-MM-dd 00:00:00');
        }
        if (!empty($dateto)) {
            $dateto = Mage::app()->getLocale()->date($dateto, Zend_Date::DATE_SHORT);
            $date = new Zend_Date();
            $date->set($dateto, Zend_Date::DATE_SHORT);
            $date->add('1', Zend_Date::DAY);
            //$dateto = $date->toString('YYYY-MM-dd 00:00:01');
            $dateto = $date->toString('dd-MM-yyyy 00:00:01');
        }

        if (!empty($datefrom) && !empty($dateto)) {
            $daterange = array("date" => true, "from" => $datefrom, "to" => $dateto);
        } else if (!empty($datefrom)) {
            $daterange = array("date" => true, "from" => $datefrom);
        } else if (!empty($dateto)) {
            $daterange = array("date" => true, "to" => $dateto);
        }

        #addFieldToFilter('total_paid',Array('gt'=>0));
        $collection = Mage::getResourceModel('sales/order_collection') // order_shipment_collection
        ->addAttributeToSelect('*')
        #->joinAttribute('invoice_id', 'invoice/increment_id', 'entity_id', 'order_id', 'left')
        //->addFieldToFilter('entity_id', $condition);
        ->addAttributeToFilter('increment_id', $condition);
        /* @var $collection Mage_Sales_Model_Mysql4_Order_Collection */
        if (!empty($storeId) || $storeId === 0) {
            $collection->addAttributeToFilter('store_id', $storeId);
        }
        if (isset($_POST['multiple'])) {
            $multiple = $_POST['multiple'];
            if (!empty($multiple)) {
                $dontupdatestatefile = true;
                $exportIds = explode(",", $multiple);
                $collection->addFieldToFilter('entity_id', $exportIds);
            }
        }
        if (isset($_POST['order_status'])) {
            $order_status = $_POST['order_status'];
            if (!empty($order_status) && $order_status != 'all') {
                $dontupdatestatefile = true;
                $collection->addFieldToFilter('status', $order_status);
            }
        }
        if (!empty($daterange)) {


            $collection->addAttributeToFilter('created_at', $daterange);
            $dontupdatestatefile = true;
        } else {
            $dontupdatestatefile = false;
        }
	
        if ($export_type == 'xml' || $export_type == 'csv' || $export_type == 'custom') {

            if (!@class_exists('XMLWriter')) {
                if (!$messages) return Mage::helper('export')->errorlog(Mage::helper('export')->__('Unable to load class XMLWriter'));
                Mage::throwException(Mage::helper('export')->__('Unable to load class XMLWriter'));
            }

            $xw = new XMLWriter;
            if (!$xw->openMemory()) {
                if (!$messages) return Mage::helper('export')->errorlog(Mage::helper('export')->__('Could not open memory for XMLWriter'));
                Mage::throwException(Mage::helper('export')->__('Could not open memory for XMLWriter'));
            } else {
                $ordercount = 0;
                $totalitemcount = 0;

                //$xw->setIndent(2);
                $xw->startDocument('1.0', 'UTF-8'); //? ISO-8859-1
                $xw->startElement('orders');

                foreach ($collection as $order) {
                	//mage::log($order);
                    $ordercount++;
                    $lastOrderId = $order->getData('entity_id');
                    $realOrderId = $order->getData('increment_id');

                    $shipping = $order->getShippingAddress();
                    $billing = $order->getBillingAddress();
                    $payment = $order->getPayment();
                    $items = $order->getAllItems();

                    $collection1 = Mage::getResourceModel('sales/order_shipment_collection')
                    ->addAttributeToSelect('increment_id')
                    ->addAttributeToSelect('created_at')
                    ->addAttributeToSelect('total_qty')
                    ->joinAttribute('shipping_firstname', 'order_address/firstname', 'shipping_address_id', null, 'left')
                    ->joinAttribute('shipping_lastname', 'order_address/lastname', 'shipping_address_id', null, 'left')
                    //            ->joinAttribute('order_increment_id', 'order/increment_id', 'order_id', null, 'left')
                    ->joinAttribute('order_created_at', 'order/created_at', 'order_id', null, 'left');

                    //			$collection1->addFieldToFilter('order_increment_id', $realOrderId);
                    $ship_date = '';
                    $ship_date_new = '';
                    foreach($collection1 as $x)
                    {
                        $ship_date = $x['created_at'];
                    }

                    if ($ship_date != '')
                    {
                        $ship_date_replace = str_replace("/","-",$ship_date);
                        $aDate = explode(" ", $ship_date_replace);
                        $aDay = explode("-", $aDate[0]);
                        $aTime = explode(":", $aDate[1]);
                        //$ship_date_new = $aDay[2] . '/' . $aDay[1] . '/' . $aDay[0];
                        $ship_date_new = $aDay[2] . $aDay[1] . $aDay[0];
                    }

                    //LF - Start - Get Shipping costs
                    $ship_collection = Mage::getModel('sales/order')->getCollection()
                    ->addFieldToFilter('increment_id', $realOrderId);

                    foreach($ship_collection as $x)
                    {
                        //Set the shipping cost to write to element
                        $ship_amount = $x['shipping_amount'];
                    }
                    //LF - End - Get Shipping costs

                    if (!isset($exportModel)) {
                        // first init so we get the export_id already here, still without creating a new id if no orders are exported
                        $exportModel = Mage::getModel('export/export');
                        $returnModel = $exportModel->save();
                        $exportid = $returnModel->getExportId();
                        $id = $exportid;
                    }

                    $xw->startElement('order');
                    
                    // Info Channels is conditional - 
                    // If there is track code the channle is = 1387 else 1385
                    $billingEmail = $billing->getEmail();
                    $customerCollection = Mage::getModel('customer/customer')->getCollection()
                    ->addAttributeToFilter('email', $billingEmail);
                    
                    if ($customerCollection->getSize() > 0) {
                    	$xw->writeElement('info_channel', '1387');
                    } else {
                    	$xw->writeElement('info_channel', '1385');
                    }
                    
                    //$xw->writeElement('shipment_date', $ship_date_new);
                    //LF - Start - Get Shipping costs
                    $xw->writeElement('shipping_amount', $ship_amount);
                    //LF - End - Get Shipping costs
                    $ship_date = '';
                    $ship_date_new = '';
                    //LF - Added Order Id to The export - Start
                    $xw->writeElement('order_id', $realOrderId);
                    $customer_dob = $order->getCustomerDob();
                    $customerDobArray = explode(" ",$customer_dob);
                    $customerDobFormatedArray = explode("-",$customerDobArray[0]);
                    $birthDAte = $customerDobFormatedArray[2]."-".$customerDobFormatedArray[1]."-".$customerDobFormatedArray[0];
                    $xw->writeElement('customer_dob',$birthDAte);
                    
                    $provience = $order->getBillingAddress()->getData('region');
                    Mage::log($order->getBillingAddress()->getData());
                    $xw->writeElement('provience', $provience);
                    
                    $xw->writeElement('coupon_code', $order->getData('coupon_code'));
                    $xw->writeElement('customer_note', $order->getData('customer_note'));
                    $xw->writeElement('customer_gender', $order->getData('customer_gender'));
                    //LF - Added Order Id to The export - End
                    $xw->writeElement('order_line_number', $ordercount);
                    $xw->writeElement('orders_count', $collection->count());
                    $xw->writeElement('export_id', $id);

                    $date = Mage::app()->getLocale()->date();
                    $xw->writeElement('current_timestamp', $date->get(null, Zend_Date::TIMESTAMP));
						
                    //Export general order data
                    if ($order) {
                        foreach($order->getData() as $key => $value) {
                        	//mage::log($key);
                        	//mage::log($value);
                            if (gettype($value) != 'array') {
                                if (gettype($value) == 'string') $value = htmlspecialchars($value, ENT_COMPAT);
                                if (!empty($key) && !empty($value)) $xw->writeElement($key, $value);
                                if ($key == 'gift_message_id') {
                                    $message = Mage::getModel('giftmessage/message');
                                    if(!is_null($value)) {
                                        $message->load((int)$value);
                                        $xw->writeElement('gift_message_sender', htmlspecialchars($message->getData('sender'), ENT_COMPAT));
                                        $xw->writeElement('gift_message_recipient', htmlspecialchars($message->getData('recipient'), ENT_COMPAT));
                                        $xw->writeElement('gift_message', htmlspecialchars($message->getData('message'), ENT_COMPAT));
                                    } else {
                                        $xw->writeElement('gift_message_sender', '');
                                        $xw->writeElement('gift_message_recipient', '');
                                        $xw->writeElement('gift_message', '');
                                    }
                                }
                                if ($key == 'created_at' && !empty($value))
                                {
                                    $date = Mage::app()->getLocale()->date($value);
                                    #2009-02-28 06:15:46
                                    #$date = new Zend_Date($value, 'yyyy-MM-dd H:i:s');

                                    //****************************************************************************
                                    //LF - Change the Date Format in the export file to DD/MM/YYYY instead of YYYY-MM-DD
                                    $date10 = $order->getData('created_at');
                                    $date1 = strtotime($date10);
                                    //$date2 = $date->get(null, Zend_Date::TIMESTAMP);
                                    $date3 = date('d/m/Y', $date1);
                                    //$date3 = '10/10/2001';
                                    //Mage::log($date3);

                                    $xw->writeElement('created_at_timestamp', $date3);

                                    //Mage::log('date 3: ' . $date3);
                                    //****************************************************************************

                                    //****************************************************************************
                                    //LF - Write Element Commented out to use our own Write Element
                                    //$xw->writeElement('created_at_timestamp', $date->get(null, Zend_Date::DATE));
                                    //****************************************************************************
                                }
                                if ($key == 'updated_at' && !empty($value))
                                {
                                    $date = Mage::app()->getLocale()->date($value);

                                    //****************************************************************************
                                    //LF - Change the Date Format in the export file to DD/MM/YYYY instead of YYYY-MM-DD

                                    $date1 = strtotime($value);
                                    $date2 = date('d/m/Y', $date1);


                                    //$xw->writeElement('created_at_timestamp', $date2);
                                    $xw->writeElement('updated_at_timestamp', $date2);
                                    //****************************************************************************

                                    //****************************************************************************
                                    //LF - Write Element Commented out to use our own Write Element
                                    //$xw->writeElement('updated_at_timestamp', $date->get(null, Zend_Date::TIMESTAMP));
                                    //****************************************************************************
                                }
                            }
                        }
                    }

                    if (Mage::getStoreConfig('admin/orderexport/enablecustomerexport', Mage::helper('export')->getSelectedStoreId())) {
                        $xw->startElement('customer');
                       
                        $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
						
                        if ($customer) {
                        	
                            foreach($customer->getData() as $key => $value) {
                            	
                                if (gettype($value) != 'array') {
                                    if (gettype($value) == 'string') $value = htmlspecialchars($value, ENT_COMPAT);
                                    if (!empty($key) && !empty($value)) $xw->writeElement($key, $value);
                                }
                            }
                        }
                        $xw->endElement();
                    }
                    //			$customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
                    //			$customer_company = $customer->getDefaultShippingAddress()->getCompany();
                    //			$xw->writeElement('company', $customer_company);
                    //			$customer_company = '';
                    //            $customerGroup = Mage::getModel('customer/group')->load($order->getCustomerGroupId());
                    //            if ($customerGroup && $customerGroup->getId()) $xw->writeElement('customer_group', $customerGroup->getCustomerGroupCode());
                    //
                    //            $couponCode = $order->getCouponCode();
                    //            if(!empty($couponCode)) {
                    //              $couponCollection = Mage::getModel('salesrule/rule')->getResourceCollection()->addFieldToFilter('coupon_code', $couponCode)->load();
                    //              if (!empty($couponCollection)) {
                    //                foreach($couponCollection as $coupon) {
                    //                  $xw->writeElement('coupon_name', $coupon->getRuleName());
                    //                  break;
                    //                }
                    //              }
                    //            }
                    //
                    //            $invoices = Mage::getResourceModel('sales/order_invoice_collection')
                    //                ->addAttributeToSelect('*')
                    //                ->setOrderFilter($order->getEntityId())
                    //                ->load();
                    //            if ($invoices->getSize() > 0) {
                    //              foreach ($invoices as $invoice) {
                    //                $xw->writeElement('invoice_id', $invoice->getIncrementId());
                    //                break;
                    //              }
                    //            }

                    //Export billing data
                    $xw->startElement('billing');
                    if ($billing) {
                        $billing->explodeStreetAddress();
                        foreach ($billing->getData() as $key => $value) {
                        	 //mage::log($key);
                        	  //mage::log($value);
                            if (gettype($value) != 'array') {
                                if (gettype($value) == 'string') $value = htmlspecialchars($value, ENT_COMPAT);
                                if (!empty($key) && !empty($value)) $xw->writeElement($key, $value);
                                if ($key == 'created_at' && !empty($value)) {
                                    $date = Mage::app()->getLocale()->date($value);
                                    $date1 = strtotime($value);
                                    $date2 = date('d/m/Y', $date1);
                                    //$xw->writeElement('created_at_timestamp', $date->get(null, Zend_Date::TIMESTAMP));
                                    $xw->writeElement('created_at_timestamp', $date2);
                                }
                                if ($key == 'updated_at' && !empty($value)) {
                                    $date = Mage::app()->getLocale()->date($value);
                                    $xw->writeElement('updated_at_timestamp', $date->get(null, Zend_Date::TIMESTAMP));
                                }
                                if ($key == 'region_id' && !empty($value)) {
                                    $region = Mage::getModel('directory/region')->load((int)$value);
                                    $xw->writeElement('region_code', $region->getData('code'));
                                    unset($region);
                                }
                            }
                        }
                    }
                    $xw->endElement();
                    //End billing data

                    //Export shipping data
                    //            $xw->startElement('shipping');
                    //            if ($shipping)
                    //			{
                    //				#$shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipping->getEntityId());
                    //				#foreach ($shipment->getAllTracks() as $track) {
                    //				#    $result['tracks'][] = $this->_getAttributes($track, 'shipment_track');
                    //				#}
                    //				$shipping->explodeStreetAddress();
                    //				foreach ($shipping->getData() as $key => $value)
                    //				{
                    //					if (gettype($value) != 'array')
                    //					{
                    //					  if (gettype($value) == 'string') $value = htmlspecialchars($value, ENT_COMPAT);
                    //					  if (!empty($key) && !empty($value)) $xw->writeElement($key, $value);
                    //					  if ($key == 'created_at' && !empty($value)) {
                    //						$date = Mage::app()->getLocale()->date($value);
                    //						$date1 = strtotime($value);
                    //						$date2 = date('d/m/Y', $date1);
                    //						//$xw->writeElement('created_at_timestamp', $date->get(null, Zend_Date::TIMESTAMP));
                    //						$xw->writeElement('created_at_timestamp', $date2);
                    //					  }
                    //					  if ($key == 'updated_at' && !empty($value)) {
                    //						$date = Mage::app()->getLocale()->date($value);
                    //						$xw->writeElement('updated_at_timestamp', $date->get(null, Zend_Date::TIMESTAMP));
                    //					  }
                    //					  if ($key == 'region_id' && !empty($value)) {
                    //						$region = Mage::getModel('directory/region')->load((int)$value);
                    //						$xw->writeElement('region_code', $region->getData('code'));
                    //						unset($region);
                    //					  }
                    //							  /*if ($key == 'street' && !empty($value)) {
                    //								$street = $value;
                    //								$street = explode(" ", $street);
                    //								if (count($street) > 0) {
                    //								  $street_name = str_replace($street[count($street)-1], '', $value);
                    //								  $street_last = $street[count($street)-1];
                    //								  if (is_numeric($street_last)) {
                    //									$street_add = '';
                    //									$street_number = $street_last;
                    //								  } else {
                    //									$street_number = intval($street_last);
                    //									$street_add = $street_last[count($street_last)+1];
                    //								  }
                    //								  $xw->writeElement('street_first', $street_name);
                    //								  $xw->writeElement('street_number', $street_number);
                    //								  $xw->writeElement('street_add', $street_add);
                    //						}
                    //							  }*/
                    //							}
                    //
                    //
                    //				}
                    //
                    //			}
                    //            $xw->endElement();
                    //End shipping data

                    //Export payment data
                    $xw->startElement('payment');
                    if ($payment) {

                    	$paymentCode = $order->getPayment()->getMethodInstance()->getCode();
                    	$adyenRef = '';
						
						if ($paymentCode == 'checkmo') {
							$opt = '';
							$PMT = '3';
						} else {

							//*********************** ADYEN PAYMENT REFERENCE**********UGLY WAY***************
							// Could not load from $order->getPayment()->getAdyenPspReference()
							// Therefore taking it out from the order history.
							
							$read = Mage::getSingleton('core/resource')->getConnection('core_read');
							$value = $read->query("select * from sales_flat_order_status_history where parent_id = '" . $order->getId() . "'");
							
							
							$row = $value->fetchAll();
							foreach ($row as $data) {
								if (strpos($data ['comment'], 'AUTHORISED') || strpos($data ['comment'], 'PENDING')) {
									$parts = explode('pspReference:', $data ['comment']);
									$adyenRef = trim($parts [1]);
								}
							}
							
							//*********************** ADYEN PAYMENT REFERENCE*************************
							
							
							// The Adyaen Payment methods are saved in this field of order.
							$adyenMethod = $order->getOnestepcheckoutCustomercomment();
							if ($adyenMethod=="bankTransfer_NL"){
									$opt = "";
									$PMT = "1";
								} elseif ($adyenMethod=="ideal"){
									$opt = "2";
									$PMT = "4";
								} else {
									$opt = "";
									$PMT = "3";
								}
						}
						
						$xw->writeElement('adyen_psp_reference', $adyenRef);
						$xw->writeElement('o_p_t', $opt); // Online Payment TYpe = ''
						$xw->writeElement('p_m_t', $PMT); // Payment Method TYpe = 2
                    	
                    }
                    $xw->endElement();
                    //End payment data

                    //***********************************************************************************
                    //LF - Start - Need to add a variable $order_created and fill the field with the created at date


                    //Start Element Club Code
                    $xw->startElement('order_created');
                    //$date10 = $order->getData('created_at');
                    $order_date_replace = str_replace("/","-",$order->getData('created_at'));
                    //$date1 = strtotime($date10);
                    //$date1 = date('d/m/Y', $date1);
                    //$strDate = $date1;

                    $aDate = explode(" ", $order_date_replace);
                    $aDay = explode("-", $aDate[0]);
                    $aTime = explode(":", $aDate[1]);
                    //$order_date_new = $aDay[2] . '/' . $aDay[1] . '/' . $aDay[0];
                    $order_date_new = $aDay[2] . $aDay[1] . $aDay[0];

                    //Load the Club code Field from the Customer Model
                    //$customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
                    //$clubcode = $customer->getClubcode();

                    //Write the Element in the XML file
                    $xw->writeElement('order_created', $order_date_new);


                    //End Club Code element.
                    $xw->endElement();
                    //***********************************************************************************


                    //LF - Shipment Date - Start

                    //shipment date is 2 days afte the order

                    //			$day = substr($order_date_new, 0,2);
                    //			$month = substr($order_date_new, 2,2);
                    //			$year = substr($order_date_new, 4,4);
                    //			$date = $year . $month . $day;
                    //
                    //			$timeStamp = strtotime($date);
                    //
                    //			$timeStamp += 24 * 60 * 60 * 1; // (add 1 days)
                    //
                    //			$shipment_date = date("dmY", $timeStamp);
                    //
                    //			$xw->writeElement('shipment_date', $shipment_date);
                    //
                    //
                    //			//LF - Shipment Date - End
                    //
                    //			//LF - check if curves cash were Used - Start
                    //			$quote_collection = Mage::getModel('sales/quote')->getCollection()
                    //					->addFieldToFilter('reserved_order_id', $realOrderId);
                    //
                    //			//loop through collection to check if curves cash were used
                    //			foreach($quote_collection as $x)
                    //			{ Mage::log("Testing Reward Points: " . $x['use_reward_points']);
                    //				if($x['use_reward_points'] > 0)
                    //				{
                    //					$xw->writeElement('curves_cash', $x['reward_currency_amount'] );
                    //					Mage::log($x['reward_currency_amount']);
                    //				}
                    //			}

                    //LF - check if curves cash were Used - End



                    //Export item data
                    $xw->startElement('items');
                    $total_weight = 0;
                    $item_qty_ordered = 0;
                    $itemcount = 0;
                    $totalqtyordered = 0;

                    // Maurice: adding in some product attribute(s) later, so get product model
                    $productModel = Mage::getModel('catalog/product')->setStoreId($order->getStoreId());
					$type = "";
					$infoChanel = "";
					
					$subscriptionContainer = array();
					
                    if ($items) {
                        foreach ($items as $item) {
                            $product = $productModel->load($item->getProductId());
                           
//                            	Mage::log($product->sku); 
                           	$skuCheck = $product->sku;
                           	// All the subscription SKU starts with SUB, so skip that from order
                           	// export.
                           	if (strpos($skuCheck, 'SUB') === 0) {
                           		
                           		// Now get Subscription and fill in $subscriptionContainer
                           		// to be used later
                           		if($product->type_id == 'simple') {
                           			$subscriptionContainer ['ipad_titel'] = $product->getAttributeText('ipad_titel');
//                            			Mage::getResourceModel('catalog/product')
//                            				->getAttributeRawValue($product->entity_id, 'ipad_titel', 0);
                           			
                           			$subscriptionContainer ['ipad_abonnementsvorm'] = $product->getAttributeText('ipad_abonnementsvorm');;
//                            			Mage::getResourceModel('catalog/product')
//                            			->getAttributeRawValue($product->entity_id, 'ipad_abonnementsvorm', 0);
                           			
                           			$subscriptionContainer ['per_price'] = str_replace('.', ',', number_format($product->ipad_maandbedrag, 2)) . ' per maand';
                           		}
                           		
                           		
                           		continue;
                           	}
                           	

                           	/*
                            if (trim($type)==""){
                            	if ($item->getProductId()>228){
                            		$type = 3;
                            	} else {
                            		$type = 2;
                            	}
                            }
                            */
                            $itemcount++;
                            $totalitemcount++;

                            $xw->startElement('item');
                            $xw->writeElement('order_product_number', $itemcount);
                            $totalqtyordered += $item->getQtyOrdered();
                            foreach ($item->getData() as $key => $val) {
                            	
                               if (gettype($val) == 'array') {
                                    continue;
                                }
                                if (gettype($val) == 'string') $value = htmlspecialchars($val, ENT_COMPAT);
                                if ($key == 'price') {
                                    // get price from product, since item price is empty for simple product
                                    $xw->writeElement($key, $product->price);
                                    continue;
                                }
                                if (!empty($key) && !empty($val)) $xw->writeElement($key, $val);
                            }

                            $xw->writeElement('product_descr', $product->description);
                            $xw->writeElement('product_type', $product->type_id);

							//mage::log($product->description);
                            $weight = (float)$product->weight;
                            $item_qty_ordered = (float)$item->getQtyOrdered();


                            $total_weight += ($weight * $item_qty_ordered);
                            //$xw->writeElement('weight', $weight);



                            if ($options = $item->getProductOptions()) {
                                $productAttributes = array();
                                $productOptions = array();
                                if (isset($options['options'])) {
                                    $productOptions = $options['options'];
                                }
                                /*if (isset($options['additional_options'])) {
                                 $result = array_merge($result, $options['additional_options']);
                                 }*/
                                if (isset($options['attributes_info'])) {
                                    $productAttributes = $options['attributes_info'];
                                }
                            }
                            if (Mage::getStoreConfig('admin/orderexport/enableproductoptions', Mage::helper('export')->getSelectedStoreId())) {
                                if (isset($productAttributes)) {
                                    $xw->startElement('product_options');
                                    foreach ($productAttributes as $attribute) {
                                        if (isset($attribute['label']) && isset($attribute['value']) && gettype($attribute['label']) == 'string' && gettype($attribute['value']) == 'string') {
                                            $xw->startElement('option');
                                            $label = htmlspecialchars($attribute['label'], ENT_COMPAT);
                                            $value = htmlspecialchars($attribute['value'], ENT_COMPAT);
                                            if (!empty($label) && !empty($value)) $xw->writeElement('name', str_replace(array('&','\'','"','<','>',' '),array('&amp;','&apos;','&quot;','&lt;','&gt;','_'), $label));
                                            if (!empty($label) && !empty($value)) $xw->writeElement('value', $value);
                                            $xw->endElement();
                                        }
                                    }
                                    $xw->endElement();
                                }
                                if (isset($productOptions)) {
                                    $xw->startElement('custom_options');
                                    foreach ($productOptions as $attribute) {
                                        if (isset($attribute['label']) && isset($attribute['value']) && gettype($attribute['label']) == 'string' && gettype($attribute['value']) == 'string') {
                                            $xw->startElement('option');
                                            $label = htmlspecialchars($attribute['label'], ENT_COMPAT);
                                            $value = htmlspecialchars($attribute['value'], ENT_COMPAT);
                                            if (!empty($label) && !empty($value)) $xw->writeElement('name', str_replace(array('&','\'','"','<','>'),array('&amp;','&apos;','&quot;','&lt;','&gt;'), $label));
                                            if (!empty($label) && !empty($value)) $xw->writeElement('value', $value);
                                            $xw->endElement();
                                        }
                                    }
                                    $xw->endElement();
                                }
                            }
                            if (Mage::getStoreConfig('admin/orderexport/enableproductattributes', Mage::helper('export')->getSelectedStoreId())) {
                                $xw->startElement('product_attributes');
                                $product = Mage::getModel('catalog/product')->setStoreId($order->getStoreId())->load($item->getProductId());
                                if ($product) {
                                    foreach ($product->getAttributes(null, true) as $attribute) {
                                        $label = $attribute->getFrontend()->getLabel();
                                        $value = $attribute->getFrontend()->getValue($product);
                                        if (!empty($label) && gettype($value) == 'string' && gettype($label) == 'string') {
                                            #$label = htmlspecialchars($label, ENT_COMPAT);
                                            $value = htmlspecialchars($value, ENT_COMPAT);
                                            $xw->writeElement($attribute->getAttributeCode(), $value);
                                            #$xw->writeElement(str_replace(array('&','\'','"','<','>',' '),array('&amp;','&apos;','&quot;','&lt;','&gt;','_'), Mage::helper('export')->XMLEntities($label)), $value);
                                        }
                                    }
                                }
                                $xw->endElement();
                            }
                            $xw->endElement();
                        }
                        
                        
                        // Shipping as Item
                        	$xw->startElement('item');
                        	$xw->writeElement('sku', '1361111301');
                        	$xw->writeElement('product_type', 'simple');
                        	$xw->writeElement('sku_type', '1');
                        	$xw->writeElement('price', '9,95');
                        	$xw->endElement();
                        
                    }
                    $xw->endElement();
                    
                   	// Subscription, Subesh
                    $xw->writeElement('per_price', @$subscriptionContainer ['per_price']);
                    $xw->writeElement('subscription_name', @$subscriptionContainer ['ipad_titel'] . ' - ' . @$subscriptionContainer ['ipad_abonnementsvorm']);
                    $xw->writeElement('bank_acc_nr', $order->getPayment()->getData('customer_bankaccount'));
                    
                    // Track Code
                    // Check for the email in customer list (imported)
                    $billingEmail = $billing->getEmail();
                    $customerCollection = Mage::getModel('customer/customer')->getCollection()
                    	->addAttributeToFilter('email', $billingEmail);
                    
                    if ($customerCollection->getSize() > 0) {
                    	$customerObj = $customerCollection->getFirstItem();
                    	$customerObj = $customerObj->load($customerObj->getId());
                    	$xw->writeElement('track_code', $customerObj->getData('affiliate_code'));
                    } 
                    
                    
                    // Coupon
                    $coupon = $order->getBillingAddress()->getData('voucher_code');
                    Mage::log($coupon);
                    $xw->writeElement('voucher_code', $coupon);

                    $xw->writeElement('order_total_qty_ordered', $totalqtyordered);
                    //End item data

                    $xw->endElement(); // Order

                    #if ($order->getStatus() == 'processing') {
                    $setStatus = Mage::getStoreConfig('admin/orderexport/setstatus', Mage::helper('export')->getSelectedStoreId());
                    if (!empty($setStatus) && $setStatus != 'no_change') {
                        if (!isset($statuses)) {
                            $statuses = array();
                            foreach (Mage::getConfig()->getNode('global/sales/order/statuses')->children() as $status) {
                                $statuses[$status->getName()] = $status;
                            }
                        }
                        if (!isset($statuses) || !isset($statuses[$setStatus])) {
                            if ($messages) Mage::getSingleton('adminhtml/session')->addNotice(Mage::helper('export')->__('The status orders should be set to after exporting could not be found. Status not changed for all orders.'));
                        } else {
                            $order->setStatus($setStatus, true)->save();
                        }
                        #}
                    }
                }

                $xw->endElement(); // Orders
                $xw->endDocument();
            }

            //die();
            // echo $xw->outputMemory(); die();

            if (!isset($lastOrderId)) {
                if ($messages) Mage::getSingleton('adminhtml/session')->addNotice(Mage::helper('export')->__('0 orders have been exported, so no new file has been created.'));
                return null;
            }

            $xwoutput = $xw->outputMemory();



            if (!@class_exists('XSLTProcessor')) {
                if ($messages) Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('export')->__('Unable to load class XSLTProcessor'));
            }
            if (!@class_exists('DOMDocument')) {
                if ($messages) Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('export')->__('Unable to load class DOMDocument'));
            }

            $files = array();

            if ((!@class_exists('DOMDocument')) || (!@class_exists('XSLTProcessor'))) {
                if ($messages) Mage::getSingleton('adminhtml/session')->addWarning('Could not load XSLTProcessor or DOMDocument, writing default xml. To fix this, please install XSLTProcessor (libxslt) and/or DOMDocument for PHP');

                if (!@file_put_contents(Mage::helper('export')->getBaseDir()."/export/".$id."_default.xml", $xw->outputMemory())) {
                    if (!$messages) return Mage::helper('export')->errorlog(Mage::helper('export')->__('Could not open export file. Please check that we\'ve got write access to '.Mage::helper('export')->getBaseDir().'/export/'.$id.'_default.xml'));
                    Mage::throwException(Mage::helper('export')->__('Could not open export file. Please check that we\'ve got write access to '.Mage::helper('export')->getBaseDir().'/export/'.$id.'_default.xml'));
                }
                $files[] = array("path" => Mage::helper('export')->getBaseDir()."/export", "id" => $id, "filename" => 'default.xml');
            } else {
                if (!$markup = Mage::getStoreConfig('admin/orderexport/'.$export_type.'markup', Mage::helper('export')->getSelectedStoreId()) || empty($markup)) {
                    if ($messages) Mage::getSingleton('adminhtml/session')->addNotice(Mage::helper('export')->__('No XML-Markup found, writing default xml.'));
                    if (!@file_put_contents(Mage::helper('export')->getBaseDir()."/export/".$id."_default.xml", $xw->outputMemory())) {
                        if (!$messages) return Mage::helper('export')->errorlog(Mage::helper('export')->__('Could not open export file. Please check that we\'ve got write access to '.Mage::helper('export')->getBaseDir().'/export/'.$id.'_default.xml'));
                        Mage::throwException(Mage::helper('export')->__('Could not open export file. Please check that we\'ve got write access to '.Mage::helper('export')->getBaseDir().'/export/'.$id.'_default.xml'));
                    }
                    $files[] = array("path" => Mage::helper('export')->getBaseDir()."/export", "id" => $id, "filename" => 'default.xml');
                } else {
                    $markup = Mage::getStoreConfig('admin/orderexport/'.$export_type.'markup', Mage::helper('export')->getSelectedStoreId());

                    # Thanks Mikkel Rikky (Systime) for this hint - allows the template to be loaded from an URL
                  // mage::log($markup);
                    $xsl = new SimpleXMLElement($markup, null, strpos($markup, '<') === false);
                    if ($xsl) {
                        $xpathres = $xsl->xpath('//files/file');
                        if ($xpathres) {
                            foreach($xpathres as $xmlel) {
                                $attributes = $xmlel->attributes();
                                if ($attributes) {
                                    //Attributes for each file
                                    $filename = $attributes->filename;
                                    $encoding = $attributes->encoding;
                                    $escaping = $attributes->escaping;
                                    $path = $attributes->path;
                                    $active = $attributes->active;
                                    $ftpupload = $attributes->ftp;
                                    $ftppath = $attributes->ftppath;
                                    if (!empty($filename) || !empty($path)) {
                                        if ($active == 'true') {
                                            $export_dir = Mage::helper('export')->getBaseDir().$path;
                                            if (!file_exists($export_dir)) {
                                                // Create export directory
                                                if (!@mkdir($export_dir)) {
                                                    if (!$messages) return Mage::helper('export')->errorlog(Mage::helper('export')->__('Could not create export directory. Please check that we\'ve got write access to "'.Mage::helper('export')->getBaseDir().'"'));
                                                    Mage::throwException(Mage::helper('export')->__('Could not create export directory. Please check that we\'ve got write access to "'.Mage::helper('export')->getBaseDir().'"'));
                                                }
                                            }
                                            if (!file_exists($export_dir."/.htaccess")) {
                                                // Create .htaccess file for directory so there is no directory listing
                                                if (!@file_put_contents($export_dir."/.htaccess", 'deny from all')) {
                                                    if (!$messages) return Mage::helper('export')->errorlog(Mage::helper('export')->__('Could not create export directory .htaccess file. Please check that we\'ve got write access to "'.Mage::helper('export')->getBaseDir().'"'));
                                                    Mage::throwException(Mage::helper('export')->__('Could not create export directory .htaccess file. Please check that we\'ve got write access to "'.Mage::helper('export')->getBaseDir().'"'));
                                                }
                                            }

                                            $row = $xmlel->xpath('*');
                                            if ($row && isset($row[0])) {
                                                $xsl = new XSLTProcessor();
                                                $xsl->registerPHPFunctions();
                                                $doc = new DOMDocument();
                                                # http://us2.php.net/manual/en/xsltprocessor.setparameter.php
                                                if ($totalitemcount) {
                                                    $xsltemplate = preg_replace(array("/\_\_TOTALITEMCOUNT\_\_/", "/\_\_EXPORTID\_\_/"), array($totalitemcount, $id), $row[0]->asXML());
                                                } else {
                                                    $xsltemplate = $row[0]->asXML();
                                                }
                                                $doc->loadXML($xsltemplate);
                                                $xsl->importStyleSheet($doc);
                                                $doc->loadXML($xwoutput);

                                                //Format filename
                                                $s = array('/%d%/', '/%m%/', '/%y%/', '/%Y%/', '/%h%/', '/%i%/', '/%s%/', '/%orderid%/', '/%realorderid%/', '/%ordercount%/', '/%uuid%/', '/%exportid%/');
                                                $r = array(Mage::getSingleton('core/date')->date('d'), Mage::getSingleton('core/date')->date('m'), Mage::getSingleton('core/date')->date('y'), Mage::getSingleton('core/date')->date('Y'), Mage::getSingleton('core/date')->date('H'), Mage::getSingleton('core/date')->date('i'), Mage::getSingleton('core/date')->date('s'), $lastOrderId, $realOrderId, $ordercount, uniqid(), $id);

                                                $filename = preg_replace($s, $r, $filename);
                                                //echo '<PRE>';
                                                //print_r($xwoutput);
                                                //echo '</PRE>';
                                                //die();
                                                //Write to file
                                                if (!empty($encoding) && @function_exists('iconv')) {
                                                    $output = iconv('UTF-8', $encoding, $xsl->transformToXML($doc));
                                                } else {
                                                    $output = $xsl->transformToXML($doc);
                                                }
                                                /*
                                                 if (isset($_POST['http_post'])) {
                                                 $ch=curl_init();
                                                 curl_setopt($ch, CURLOPT_URL, '');
                                                 curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                                 curl_setopt($ch, CURLOPT_POST, 1) ;
                                                 curl_setopt($ch, CURLOPT_POSTFIELDS, urlencode($output));
                                                 curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                                                 curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                                                 $result = curl_exec($ch);
                                                 curl_close($ch);
                                                 if ($messages) Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('export')->__('Post returned: '.$result));
                                                 }
                                                 */
                                                if ($escaping == 'true') {
                                                    $output = Mage::helper('export')->XMLEntities($output);
                                                } else {
                                                    $output = html_entity_decode($output, ENT_COMPAT);
                                                }

                                                if (!@file_put_contents($export_dir."/".$id."_".$filename, $output)) {
                                                    //Mage::throwException(Mage::helper('export')->__('Could not open export file. Please check that we\'ve got write access to "'.$export_dir."/".$id."_".$filename.'"'));
                                                } else {
                                                    $files[] = array("path" => $export_dir, "origpath" => $attributes->path, "id" => $id, "filename" => $filename, "ftpupload" => $ftpupload, "ftppath" => $ftppath);
                                                }

                                                if ($ftpupload == 'true') $doftpupload = true;

                                                unset($xsl);
                                                unset($doc);
                                            }
                                        }
                                    } else {
                                        if (!$messages) return Mage::helper('export')->errorlog(Mage::helper('export')->__('Could not find attributes in XML Markup ('.htmlspecialchars('<files><file active="true" ...>...</file></files>').')'));
                                        Mage::throwException(Mage::helper('export')->__('Could not find attributes in XML Markup ('.htmlspecialchars('<files><file active="true" ...>...</file></files>').')'));
                                    }
                                } else {
                                    if (!$messages) return Mage::helper('export')->errorlog(Mage::helper('export')->__('Could not find attributes in XML Markup ('.htmlspecialchars('<files><file active="true" ...>...</file></files>').')'));
                                    Mage::throwException(Mage::helper('export')->__('Could not find attributes in XML Markup ('.htmlspecialchars('<files><file active="true" ...>...</file></files>').')'));
                                }
                            }
                        } else {
                            if (!$messages) return Mage::helper('export')->errorlog(Mage::helper('export')->__('Could not find attributes in XML Markup ('.htmlspecialchars('<files><file active="true" ...>...</file></files>').')'));
                            Mage::throwException(Mage::helper('export')->__('Could not find attributes in XML Markup ('.htmlspecialchars('<files><file active="true" ...>...</file></files>').')'));
                        }
                    } else {
                        if (!$messages) return Mage::helper('export')->errorlog(Mage::helper('export')->__('Could not find XML Markup ('.htmlspecialchars('<files><file active="true" ...>...</file></files>').')'));
                        Mage::throwException(Mage::helper('export')->__('Could not find XML Markup ('.htmlspecialchars('<files><file active="true" ...>...</file></files>').')'));
                    }

                }
            }

            unset($xw);
        }

        $ftpstatus = 0;
        if (isset($doftpupload) && count($files) > 0) {
            $ftpstatus = 2;
            $server = Mage::getStoreConfig('admin/orderexportftp/server', Mage::helper('export')->getSelectedStoreId());
            $port = Mage::getStoreConfig('admin/orderexportftp/port', Mage::helper('export')->getSelectedStoreId());
            $username = Mage::getStoreConfig('admin/orderexportftp/username', Mage::helper('export')->getSelectedStoreId());
            $password = Mage::getStoreConfig('admin/orderexportftp/password', Mage::helper('export')->getSelectedStoreId());
            $path = Mage::getStoreConfig('admin/orderexportftp/path', Mage::helper('export')->getSelectedStoreId());
            $usessl = Mage::getStoreConfig('admin/orderexportftp/usessl', Mage::helper('export')->getSelectedStoreId());
            if (!empty($server) && !empty($port) && !empty($username) && !empty($password)) {
                if ($usessl) {
                    if (function_exists('ftp_ssl_connect')) {
                        $conn = @ftp_ssl_connect($server, (int)$port, 15);
                    } else {
                        if ($messages) Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('export')->__('No FTP-SSL functions found.'));
                    }
                } else {
                    if (function_exists('ftp_connect')) {
                        $conn = @ftp_connect($server, (int)$port, 15);
                    } else {
                        if ($messages) Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('export')->__('No FTP functions found.'));
                    }
                }
                if ($conn) {
                    if (@ftp_login($conn, $username, $password)) {
                        foreach ($files as $file) {
                            if ($file['ftpupload'] == 'true') {
                                $fpath = (isset($file['ftppath']))?$file['ftppath']:$path;
                                if (@ftp_put($conn, $fpath.$file['filename'], $file['path']."/".$id."_".$file['filename'], FTP_BINARY)) {
                                    $ftpstatus = 1;
                                }
                                if ($ftpstatus == 1 && $messages) Mage::getSingleton('adminhtml/session')->addSuccess('Export uploaded successfully to FTP server.');
                                else if ($messages && $ftpstatus == 2) Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('export')->__("Could not upload export to '".$fpath.$file['filename']." from ".$file['path']."/".$id."_".$file['filename']."' to FTP server."));
                            }
                        }
                    } else {
                        if ($messages) Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('export')->__('Wrong login for FTP-Server.'));
                    }
                    ftp_quit($conn);
                } else {
                    if ($messages) Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('export')->__('Could not connect to FTP-Server.'));
                }
            }
        }

        if (empty($files)) {
            if (!$messages) return Mage::helper('export')->errorlog(Mage::helper('export')->__('No export files have been created, stopping export.'));
            Mage::throwException(Mage::helper('export')->__('No export files have been created, stopping export.'));
        }

        $dbfiles = '';
        $displayfiles = '';
        foreach ($files as $file) {
            if (empty($dbfiles) && empty($displayfiles)) {
                $dbfiles = $file['origpath']."/".$id."_".$file['filename'];
                $displayfiles = $file['filename'];
            } else {
                $dbfiles = $dbfiles.','.$file['origpath']."/".$id."_".$file['filename'];
                $displayfiles = $displayfiles.','.$file['filename'];
            }
        }

        try {
            if ($ftpstatus == 1 && Mage::getStoreConfig('admin/orderexportftp/setstatus', Mage::helper('export')->getSelectedStoreId())) $exportModel->setDownloaded(1);
            $returnModel = $exportModel->setFiles($dbfiles)
            ->setDisplayfiles($displayfiles)
            ->setType($export_type)
            ->setCount($ordercount)
            ->setFtpupload($ftpstatus)
            ->setAutoexport($auto)
            ->setCreated(Mage::getSingleton('core/date')->gmtDate())
            ->save();
        } catch (Exception $e) {
            if ($messages) Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        if (!$dontupdatestatefile) {
            if (!file_exists(Mage::helper('export')->getBaseDir()."/export/")) {
                // Create export directory
                if (!@mkdir(Mage::helper('export')->getBaseDir()."/export/")) {
                    if (!$messages) return Mage::helper('export')->errorlog(Mage::helper('export')->__('Could not create export directory. Please check that we\'ve got write access to "'.Mage::helper('export')->getBaseDir().'"'));
                    Mage::throwException(Mage::helper('export')->__('Could not create export directory. Please check that we\'ve got write access to "'.Mage::helper('export')->getBaseDir().'"'));
                }
            }
            if (!file_put_contents(Mage::helper('export')->getBaseDir()."/export/export.state", $realOrderId)) {
                if (!$messages) return Mage::helper('export')->errorlog(Mage::helper('export')->__('Could not create export directory. Please check that we\'ve got write access to "'.Mage::helper('export')->getBaseDir().'"'));
                Mage::throwException(Mage::helper('export')->__('Could not create export state file. Please check that we\'ve got write access to "'.Mage::helper('export')->getBaseDir().'"'));
            }
        }

        if ($messages) Mage::getSingleton('adminhtml/session')->addSuccess($ordercount.' '.sprintf(Mage::helper('export')->__('orders have been exported successfully. Click here to download the export file: <a href="%s">%s</a>'), Mage::getSingleton('adminhtml/url')->getUrl('export/index/get')."export_id/".$exportid, Mage::helper('export')->__('Download File')));

        return $exportid;
    }

}