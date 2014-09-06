<?php
error_reporting(E_ALL | E_STRICT);
$mageFilename = './app/Mage.php';
$error = array();
$success = array();
require_once $mageFilename;
Mage::setIsDeveloperMode(true);
umask(0);
Mage::app()->setCurrentStore(Mage::getModel('core/store')->load(Mage_Core_Model_App::ADMIN_STORE_ID));

$subscriptionMap = array();

/**
 * Convert a comma separated file into an associated array.
 * The first row should contain the array keys.
 * 
 * Example:
 * 
 * @param string $filename Path to the CSV file
 * @param string $delimiter The separator used in the file
 * @return array
 * @link http://gist.github.com/385876
 * @author Jay Williams <http://myd3.com/>
 * @copyright Copyright (c) 2010, Jay Williams
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
function csv_to_array($filename='', $delimiter=',')
{
	if(!file_exists($filename) || !is_readable($filename))
		return FALSE;
	
	$header = NULL;
	$data = array();
	if (($handle = fopen($filename, 'r')) !== FALSE)
	{
		while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
		{
			if(!$header)
				$header = $row;
			else
				$data[] = array_combine($header, $row);
		}
		fclose($handle);
	}
	return $data;
}

function verifyRowValues(&$rowValue)
{
	$helper = Mage::helper('core/string');
	$requiredKey = array(
			'GESLACHT', 'INITIALEN', 'ACHTERNAAM', 'STRAAT',
			'HUISNUMMER', 'POSTCODE', 'WOONPLAATS', 'LAND',
			'EMAILADRES', 'REKENINGNUMMER', 'GEBOORTEDATUM',
			'ABONNEMENTSVORM', 'AFFILIATEPARTNERCODE'
	);
	foreach ($requiredKey as $_key) {
		$value = trim($helper->cleanString($rowValue[$_key]));
		if (empty($value)) {
			echo "Empty " . $_key . "\n";
			return false;
		}
	}
	return true;
}

function resolve_code_name($subscriptionValue)
{
	global $subscriptionMap;
// 	echo "<PRE>";
// 	print_r($subscriptionMap);
// 	exit;
	if (isset($subscriptionMap[strtoupper($subscriptionValue)])) {
		return $subscriptionMap[strtoupper($subscriptionValue)];
	}
	return false;
}

function _initMap()
{
	global $subscriptionMap;
	$map = Mage::getModel('subscribers/subscribers')->getCollection();
	if ($map->getSize() > 0) {
		foreach ($map as $_map){
			$subscriptionMap [strtoupper($_map['name'])] = $_map['sku']; 
		}
	}
}

if(isset($_FILES['csv_subs'])){
	
	$file = Mage::getBaseDir().'/var/'.$_FILES['csv_subs']['name'];
	@unlink($file);

	if(move_uploaded_file($_FILES['csv_subs']['tmp_name'], $file)){

		ini_set('max_execution_time', 99999);
		$_resource = Mage::getSingleton('core/resource');
		$helper = Mage::helper('core/string');
		
		$data = csv_to_array($file, ';');
		
		$countryArray = Mage::getModel('directory/country_api')->items();

		if(!is_array($data)) $data = array();
		
		$counter = 0;
		
		_initMap();
		
		foreach($data as $i => $row){
			
			$okFlag = true; //states whether row can be executed
			
			$validate = verifyRowValues($row);

			if(!$validate ) {
				$error [] =  $i . " row's required fields are not set.";
				continue; //nothing to do if validation error in row
			} 

			$customer = Mage::getModel('customer/customer')
				->setWebsiteId(1)->loadByEmail($row['EMAILADRES']);
			
			$newCustomerFlag = false;
			
			if( ! $customer->getId() ){
				$newCustomerFlag = true;
				
				$customer = Mage::getModel('customer/customer')->setId(null);
				$customer->setWebsiteId(1);
			}
			$customer->setEmail($row ['EMAILADRES']);			
			$customer->setGender( (strtolower($row['GESLACHT']) === 'm' ? 123 : 124) );
		    $customer->setFirstname($row['INITIALEN']); // First name is Initials (Gerrit's Email)
			$customer->setMiddlename($row['VOORVOEGSEL']); // Middle Name (VOORVOEGSEL)
			$customer->setLastname($row['ACHTERNAAM']);
			
			$dob = $row['GEBOORTEDATUM']; // Comes as 13-5-1976 change to 1976-05-13
			$dobParts = explode('-', $dob);
			$formatDob = $dobParts[2] . '-' . str_pad($dobParts[1], 2,'0',STR_PAD_LEFT) . '-' . $dobParts[0];
			$customer->setDob($formatDob);
			
			$customer->setBankAccountNr($row['REKENINGNUMMER']);
			
			$customer->setAffiliateCode($row['AFFILIATEPARTNERCODE']);
			
			// Resolve Subscription Code From Map Table
			$code = resolve_code_name($row['ABONNEMENTSVORM']);
			if (!$code) {
				$error[] = $row['EMAILADRES'] . ' row has no matching subscription Sku.';
				$i++;
				continue; 
			}
			$customer->setSubscriptionCode($code);
			

			$countryCode = 'NL';
			
			$customer->setCountryId($countryCode);
			$customer->save();
			
			
			
			$_custom_address = array (
				'firstname' => $row['INITIALEN'], // First name is Initials (Gerrit's Email)
				'middlename' => $row['VOORVOEGSEL'],
				'lastname' => $row['ACHTERNAAM'],
				'street' => array (
					'0' => $row['STRAAT'] ,
					'1' => $row['HUISNUMMER'] ,
					'2' => $row['HUISNUMMER_TOEVOEGING'],
				),
				'city' => $row['WOONPLAATS'],
				'postcode' => $row['POSTCODE'],
				'country_id' => $countryCode,
				'telephone' => (!empty($row['TELEFOONNUMMER'])) ? $row['TELEFOONNUMMER'] : $row['MOBIELNUMMER'],
			);
			
				
			$customAddress = Mage::getModel('customer/address')
				->setData($_custom_address)
				->setCustomerId($customer->getId())
				->setIsDefaultBilling('1')
				->setIsDefaultShipping('1')
				->setSaveInAddressBook('1')
				->save();
			
			// Load again for address.
			$customer->load(($customer->getId()));
				$customer->setConfirmation(null);
				$customer->save();
				
			if($counter >= 15){
				$counter = 0;
				sleep();
			}
			$success[] = "Row $i has been imported";
		}
	}
}

$errHtml = null;
$successHtml = null;

if( count($error) ){
	$errHtml = '<ul class="messages"><li class="error-msg"><div>There were some errors</div><ul>';
	foreach($error as $e){
		$errHtml .= '<li>' . $e . '</li>';
	}

	$errHtml .= '</ul></li></ul>';
}

if( count($success) ){
	$successHtml = '<ul class="messages"><li class="success-msg"><ul><li>Records were added successfully!</li></ul></li></ul>';
}

?>

<html>
	<head>
		<style>
			.messages, .error-msg ul, success-msg ul{list-style: none;}
			.error-msg{color:#FF4545;}
			.success-msg{color:#0a0}
		</style>
	</head>
<body>
<?php //Mage::app()->getLayout()->getMessagesBlock()->setMessages(Mage::getSingleton('core/session')->getMessages(true))->getGroupedHtml();?>
<?php echo $errHtml?>
<?php echo $successHtml?>
<form action="" enctype="multipart/form-data" method="post">
<fieldset>
	<legend>Upload CSV File that contains customer(s) detail</legend>
	<input type="file" name="csv_subs" />
	<br/><br/>
	<input type="submit" value="Upload" />
</fieldset>
</form>
</body>
</html>