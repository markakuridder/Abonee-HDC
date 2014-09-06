<?php
class Crimsonwing_Validate_Number
{
	
	public function validateNumber($checkValue)
	{
		Mage::log("CHECKING NUMBER " . $checkValue);
		// verwijder spaties voor / achter het nummer
		$banknr = trim($checkValue);
		// is het nummer leeg --> fout
		if ($banknr == '') {
			return false;
			//form_set_error('bankrekening', 'Bankrekening is verplicht');
		}
		// rekeningnummer: alleen cijfers
		else if (!is_numeric($banknr)) {
			//form_set_error('bankrekening', 'De bankrekening bevat ongeldige tekens');
		} else if ($banknr == 0) { // is het nummer 0?
				return false;
				//form_set_error('bankrekening', 'De bankrekening is ongeldig');
		} else {
			// verwijder voorloopnullen
			while (substr($banknr, 0, 1) == '0' && strlen($banknr > 0)) {
				$banknr = substr($banknr, 1);
			}
			// rekeningnummer: controle op lengte
			$len = strlen($banknr);
			if ($len > 10) {
				return false; //The bank has too many digits
// 				return 'De bankrekening bevat te veel cijfers';
			}
		
			// lengtecontrole alleen op lengte 8: controle hierboven:
			//  minimaal 1, veldlengte = maximaal 9 of 10 cijfers: in de formulier opties te zetten
			switch ($len) {
				case 9:
				case 10:
					// bankrekening -> controle met elfproef
					if ($this->_elfproef($banknr))
						return false;
						//form_set_error('bankrekening', 'De bankrekening is onbestaanbaar');
					break;
				case 8:
					return false;
					// een Nederlands rekeningnummer kan nooit 8 lang zijn
					//form_set_error('bankrekening', 'De bankrekening is fout: een rekeningnummer van 8 cijfers bestaat niet');
					break;
				default:
					// een postbank-nummer
					break;
			}
			return true;
		}
	}
	
	/**
	* controleer een banknummer met behulp van de elfproef
	* returns: true als fout, false als voldoet aan de proef
	*/
	protected function _elfproef($banknr) {
		$res = 0;
		// vermenigvuldigingsfactor = lengte van de string.
		// Officieel kan een banknummer 9 of 10 cijfers zijn !
		$verm = strlen($banknr);
		for ($i = 0; $i < strlen($banknr); $i++, $verm--) {
			$res += substr($banknr, $i, 1) * $verm;
		}
		return ($res % 11);
	}
}