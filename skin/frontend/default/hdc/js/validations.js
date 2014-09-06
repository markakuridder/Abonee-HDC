Validation.add('validate-nl-phone', 'Vul alstublieft een 10-cijferig telefoonnummer in.', function(value) {
    if (value.replace(/[^0-9]/g, '').match(/^[0-9]{10}$/)) {
        return true;
    }
    return false;
});
Validation.add('validate-nl-postcode', 'Vul alstublieft een geldige postcode in.', function(value) {
    if (value.match(/^[0-9]{4}\s*[a-z]{2}$/i)) {
        return true;
    }
    return false;
});
Validation.add('validate-1994', 'U moet 18 jaar of ouder zijn om aan deze actie deel te nemen.', function(value) {
    if (parseInt(value) <= 1995) {
        return true;
    }
    return false;
});
Validation.add('validate-banknr', 'Bankrekening nummer moet niet de ruimte of punten bevatten.', function(value) {
    if (value.match(' ')) return false;
    //if (value.strlen() > 18) return false;
	if(value.indexOf('.') === -1 && 
    		value.indexOf(',') === -1 && value.indexOf('-') === -1){
    	return true;
    }
	return false;
});