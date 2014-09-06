<?php
class Crimsonwing_Validate_Isbn
{
	/**
	 * Controleer IBAN / SEPA nummer
	 *
	 * @param $rekening
	 *   De te controleren IBAN/SEPA-rekening
	 *
	 * @return
	 *   Foutboodschap of lege string als geen fout gevonden
	 *
	 * @info
	 *   http://www.ecbs.org
	 *   http://www.tbg5-finance.org/?ibandocs.shtml/
	 *   http://en.wikipedia.org/wiki/IBAN
	 *   http://nl.wikipedia.org/wiki/International_Bank_Account_Number
	 *   http://www.swift.com/dsp/resources/documents/IBAN_Registry.pdf
	 */
	public function iban_controle($rekening) {
		
		Mage::log("CHECKING ISBN " . $rekening);
		// verwijder tussenliggende spaties en zet om in hoofdletters
		$banknr = strtoupper ( str_replace ( ' ', '', trim ( $rekening ) ) );
	
		// De eerste twee tekens moeten een twee letter landcode zijn
		$country = substr ( $banknr, 0, 2 );
		$info = $this->_iban_landcode($country);
		if (! is_array ( $info )) {
			return false;
			return 'De landcode (eerste twee tekens) is onbekend.'; //The country code (first two digits) is unknown.
		}
	
		// controleer de lengte
		if (strlen ( $banknr ) != $info [0]) {
			return false;
			return 'De lengte van het IBAN is onjuist.'; //The length of the IBAN is incorrect.
		}
	
		// controleer het formaat
		$parts = explode ( ',', '2n,' . $info [1] );
		$i = 2;
		foreach ( $parts as $format ) {
			$len = substr ( $format, 0, strlen ( $format ) - 1 );
			$string = substr ( $banknr, $i, $len );
			$i += $len;
			$error = FALSE;
			switch (substr ( $format, - 1 )) {
				// alfanumeriek
				case 'a' :
					if (! ctype_alpha ( $string )) {
						$error = TRUE;
					}
					break;
						
					// willekeurig teken (alfanumeriek)
				case 'c' :
					if (! ctype_alnum ( $string )) {
						$error = TRUE;
					}
					break;
				case 'n' :
					if (! is_numeric ( $string )) {
						$error = TRUE;
					}
					break;
				default :
					return false;
					return 'Ongeldige controle code.'; // Invalid Verification code
					break;
			}
			if ($error) {
				return false;
				return 'Het IBAN is ongeldig.';
			}
		}
	
		// zet het nummer om tbv de controle
		// zet eerst de landcode achter de rest
		// daarachter het controlegetal
	
	
		//Put the number serving to control
		// Set the first country code behind the rest
		// Behind the checksum
	
		$nr = substr ( $banknr, 4 ) . substr ( $banknr, 0, 4 );
		// vervang de letters door cijfers: A=10 t/m Z=35
		$nwnr = '';
		for($i = 0; $i < strlen ( $nr ); $i ++) {
			$ch = substr ( $nr, $i, 1 );
			if ($ch >= '0' && $ch <= '9') {
				$nwnr .= $ch;
			} elseif ($ch >= 'A' && $ch <= 'Z') {
				// ord('A') = 65 => zet om in '10' (= 65 - 65 + 10)
				// ord('Z') = 90 => zet om in '35' (= 90 - 65 + 10)
				$nwnr .= strval ( ord ( $ch ) - 55 );
			} else {
				return false;
				return 'Het IBAN bevat ongeldige tekens.'; // In valid Characters
			}
		}
		if ($this->_iban_mod97 ( $nwnr )) {
			return false;
			return 'Het IBAN is ongeldig.'; // Invalide
		}
		return true;
		return '';
	}
	
	/**
	 * Controleer IBAN Landcode op bestaanbaarheid
	 *
	 * source: http://en.wikipedia.org/wiki/IBAN#List_of_valid_IBANs_by_country
	 *
	 * returns: IBAN-lengte + opmaakformaat van het land of lege array
	 *
	 * status: 2012-01-06
	 */
	protected function _iban_landcode($country) {
		
		static $countries = array(
				'AL' => array(28, '8n,16c'),     // Albania
				'AD' => array(24, '8n,12c'),     // Andorra
				'AT' => array(20, '16n'),        // Austria
				'AZ' => array(28, '4a,20c'),     // Replublic of Azerbaijan
				'BE' => array(16, '12n'),        // Belgium
				'BH' => array(22, '4a,14c'),     // Bahrain
				'BA' => array(20, '16n'),        // Bosnia and Herzegovina
				'BG' => array(22, '4a,6n,8c'),   // Bulgaria
				'CR' => array(21, '17n'),        // Costa Rica
				'HR' => array(21, '17n'),        // Croatia
				'CY' => array(28, '8n,16c'),     // Cyprus
				'CZ' => array(24, '20n'),        // Czech Republic
				'DK' => array(18, '14n'),        // Denmark
				'DO' => array(28, '4c,20n'),     // Dominican Republic
				'EE' => array(20, '16n'),        // Estonia
				'FO' => array(18, '14n'),        // Faroe Islands
				'FI' => array(18, '14n'),        // Finland
				'FR' => array(27, '10n,11c,2n'), // France
				'GE' => array(22, '2a,16n'),     // Georgia
				'DE' => array(22, '18n'),        // Germany
				'GI' => array(23, '4a,15c'),     // Giraltar
				'GR' => array(27, '7n,16c'),     // Greece
				'GL' => array(18, '14n'),        // Greenland
				'GT' => array(28, '24c'),        // Guatemala
				'HU' => array(28, '24n'),        // Hungary
				'IS' => array(26, '22n'),        // Iceland
				'IE' => array(22, '4a,14n'),     // Ireland
				'IL' => array(23, '19n'),        // Israel
				'IT' => array(27, '1a,10n,12c'), // Italy
				'KZ' => array(20, '3n,13c'),     // Kazakhstan
				'KW' => array(30, '4a,22c'),     // Kuwait
				'LV' => array(21, '4a,13c'),     // Latvia
				'LB' => array(28, '4n,20c'),     // Lebanon
				'LI' => array(21, '5n,12c'),     // Liechtenstein
				'LT' => array(20, '16n'),        // Lithuania
				'LU' => array(20, '3n,13c'),     // Luxembourg
				'MK' => array(19, '3n,10c,2n'),  // Macedonia
				'MT' => array(31, '4a,5n,18c'),  // Malta
				'MR' => array(27, '23n'),        // Mauritania
				'MU' => array(30, '4a,19n,3a'),  // Mauritius
				'MD' => array(24, '2a,18n'),     // Republic of Moldova
				'MC' => array(27, '10n,11c,2n'), // Monaco
				'ME' => array(22, '18n'),        // Montenegro
				'NL' => array(18, '4a,10n'),     // Netherlands
				'NO' => array(15, '11n'),        // Norway
				'PK' => array(24, '4a,16c'),     // Pakistan
				'PL' => array(28, '24n'),        // Poland
				'PT' => array(25, '21n'),        // Portugal
				'RO' => array(24, '4a,16c'),     // Romania
				'SM' => array(27, '1a,10n,12c'), // San Marino
				'SA' => array(24, '2n,18c'),     // Saudi Arabia
				'RS' => array(22, '18n'),        // Serbia
				'SK' => array(24, '20n'),        // Slovakia
				'SI' => array(19, '15n'),        // Slovenia
				'ES' => array(24, '20n'),        // Spain
				'SE' => array(24, '20n'),        // Sweden
				'CH' => array(21, '5n,12c'),     // Switzerland
				'TN' => array(24, '20n'),        // Tunisia
				'TR' => array(26, '5n,17c'),     // Turkey
				'AE' => array(23, '19n'),        // United Arab Emirates
				'GB' => array(22, '4a,14n'),     // United Kingdom
				'VG' => array(24, '4a,16n'),     // Virgin Islands, British
		);
		return @$countries[$country];
	}
	
	/**
	 * kontroleer een IBAN  mbv Mod 10,97 (ISO/IEC 7064:2003) model.
	 *
	 * returns: true als fout, false als voldoet aan de proef
	 */
	protected function _iban_mod97($iban) {
		$parts = ceil(strlen($iban) / 7);
		$remainder = '';
		for ($i = 0; $i < $parts; $i++) {
			$remainder = strval(intval(($remainder . substr($iban, $i * 7, 7))) % 97);
		}
		return intval($remainder) != 1;
	}
}