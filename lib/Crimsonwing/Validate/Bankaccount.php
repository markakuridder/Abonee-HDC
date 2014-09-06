<?php
/**
 * @see Zend_Validate_Abstract
 */
#require_once 'Zend/Validate/Abstract.php';


/**
 * @category   Crimsonwing
 * @package    Crimsonwing_Validate
 * @copyright  Copyright (c) 2010-2011 Crimsonwing (www.crimsonwing.com)
 */
class Crimsonwing_Validate_Bankaccount extends Zend_Validate_Abstract
{
    /**
     * Validation failure message key for when the value is not of valid length
     */
    const LENGTH   = 'bankaccountLength';

    /**
     * Validation failure message key for when the value fails the 11 checksum
     */
    const CHECKSUM = 'bankaccountChecksum';

    /**
     * Digits filter for input
     *
     * @var Zend_Filter_Digits
     */
    protected static $_filter = null;

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = array(
        self::LENGTH   => "'%value%' must contain 7 - 10 digits",
        self::CHECKSUM => "Elf check failed on '%value%'"
    );

    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if length = 7, or if 9 => length <=10 and adheres to the famous eleven (% 11) check :p
     *
     * @param  string $value
     * @return boolean
     */
//     public function isValid($value)
//     {
//         $this->_setValue($value);

//         if (null === self::$_filter) {
//             /**
//              * @see Zend_Filter_Digits
//              */
//             #require_once 'Zend/Filter/Digits.php';
//             self::$_filter = new Zend_Filter_Digits();
//         }

//         $valueFiltered = self::$_filter->filter($value);

//         $length = strlen($valueFiltered);

//         if ($length < 3 || $length == 8 || $length > 10) {
//             $this->_error(self::LENGTH);
//             return false;
//         }
//         // some numbers that pass the elf check, but are not valid
//         if ($valueFiltered == '000000000' || $valueFiltered == '111111110' || $valueFiltered == '999999990' || $valueFiltered == '123456789') {
//             $this->_error(self::CHECKSUM);
//             return false;
//         }

//         if ($length >= 3 && $length < 8) {
//             return true; // small number or postbank, wont validate
//         }

//         $res = 0;
//         // vermenigvuldigingsfactor = lengte van de string.
//         // Officieel kan een banknummer 9 of 10 cijfers zijn !
//         $pos = strlen($value);
//         for ($i = 0; $i < strlen($value); $i++, $pos--) {
//             $res += substr($value, $i, 1) * $pos;
//         }
//         if (($res % 11) !== 0) {
//             $this->_error(self::CHECKSUM);
//             return false;
//         };
//         return true;
//     }

    /**
     * (non-PHPdoc)
     * @see Zend_Validate_Interface::isValid()
     * 
	    //http://www.drupalhandboek.nl/controle-iban-sepa-rekening
	    //http://www.drupalhandboek.nl/controle-bankrekening-met-elfproef
     */
    
    public function isValid($value)
    {
    	try {
	    	$this->_setValue($value);
	    	Mage::log($value);
	    	$isbnCheck = new Crimsonwing_Validate_Isbn();
	    	$isbnValid = $isbnCheck->iban_controle($value);
// 	    	var_dump($isbnValid);
	    	if ($isbnValid){
	    		return true;
	    	}
// 	    	exit;
	    	$checkNumber = new Crimsonwing_Validate_Number();
	    	$isValidNumber = $checkNumber->validateNumber($value);
	    	if ($isValidNumber) {
	    		return true;
	    	}
	    	return false;
	    	
    	}catch (Exception $e){
    		Mage::log($e->getMessage());
    	}
    }

}
