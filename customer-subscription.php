<?php
error_reporting(E_ALL | E_STRICT);
$mageFilename = './app/Mage.php';
$error = array();
$success = array();
require_once $mageFilename;
Mage::setIsDeveloperMode(true);
umask(0);
Mage::app()->setCurrentStore(Mage::getModel('core/store')->load(Mage_Core_Model_App::ADMIN_STORE_ID));


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



if(isset($_FILES['csv_subs'])){
	
	$file = Mage::getBaseDir().'/var/'.$_FILES['csv_subs']['name'];
	@unlink($file);

	if(move_uploaded_file($_FILES['csv_subs']['tmp_name'], $file)){

		ini_set('max_execution_time', 99999);
		$_resource = Mage::getSingleton('core/resource');
		$helper = Mage::helper('core/string');
		
		$data = csv_to_array($file, ';');
		

		if(!is_array($data)) $data = array();
		
		$counter = 0;
		
		//Clean All
		$collection = Mage::getModel('subscribers/subscribers')->getCollection();
		if ($collection->getSize() > 0) {
			foreach ($collection as $_model){
				$_model->delete();
			}
		}
		
		foreach($data as $i => $row){
			$model = Mage::getModel('subscribers/subscribers')->clearInstance();
			$model->setName(strtoupper($row['NAME']));
			$model->setSku(strtoupper($row['SKU']));
			$model->save();
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
	<legend>Upload CSV File that contains subscription & SKU detail Map</legend>
	<input type="file" name="csv_subs" />
	<br/><br/>
	<input type="submit" value="Upload" />
</fieldset>
</form>
</body>
</html>