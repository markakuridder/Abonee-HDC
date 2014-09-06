<?php
ini_set('memory_limit', '2048M');
error_reporting(E_ALL | E_STRICT);
$mageFilename = './app/Mage.php';
$error = array();
$success = array();
require_once $mageFilename;
Mage::setIsDeveloperMode(true);
umask(0);
Mage::app()->setCurrentStore(Mage::getModel('core/store')->load(Mage_Core_Model_App::ADMIN_STORE_ID));


// Create Index
$_resource = Mage::getSingleton('core/resource');
$read = $_resource->getConnection('core_read');
$write = $_resource->getConnection('core_write');
// print_r($read);
// print_r($_resource);
// $query = "ALTER TABLE subscriptions DROP id";
// $write->query($query);
// $query = "CREATE UNIQUE INDEX id_unique_all ON subscriptions ( postcode, houseno,houseno_ext);";
// $write->query($query);
// exit;

$file = Mage::getBaseDir().'/var/import/subs/import.csv';


function csv_to_array($filename='', $delimiter=',')
{
	if(!file_exists($filename) || !is_readable($filename))
		return FALSE;

	$header = array(1,2,3);
	$data = array();
	if (($handle = fopen($filename, 'r')) !== FALSE)
	{
		while (($row = fgetcsv($handle, 1000, $delimiter,'"')) !== FALSE)
		{
			if (!isset($row [2])) {
				$row [2] = '';
			}
// 			if(!$header)
// 				$header = $row;
			else
				$data[] = array_combine($header, $row);
		}
		fclose($handle);
	}
	return $data;
}

// function flattenArray(array $array){
// 	$ret_array = array();
// 	foreach(new RecursiveIteratorIterator($array) as $value)
// 	{
// 		$ret_array[] = $value;
// 	}
// 	return $ret_array;
// }
$data = csv_to_array($file, ';');
// $data = flattenArray($data);


$count = count($data);
$size = 500;
$loop = ceil($count/$size);
$init= 1;
for ($i = 1; $i <= $loop; $i++) {
	$_temp = array_slice($data, $init, $size);
	$init = $i * $size;
	//     echo "INIT - ". $init;
	//     echo "\n";
	//     echo count($_temp);
	//     echo "\n";
	insert_temp($_temp);
	//     disable($_temp);

}

function insert_temp($data){
	global $write;
	try {
		$query = "INSERT IGNORE INTO subscriptions (postcode,houseno,houseno_ext) values ";
		$queryParts = array();
		foreach ($data as  $_row) {
			$queryParts [] = "('".trim(@$_row [1]) . "','". trim(@$_row [2]) .
			"','". trim(@$_row [3]) ."')";
			
		}
		 
		$query .= implode(",", $queryParts);
 		$write->query($query);
		 
	} catch (Exception $e) {
		echo $e->getMessage();
		echo "\n";		
		Mage::log($e->getMessage());
	}
}

?>

