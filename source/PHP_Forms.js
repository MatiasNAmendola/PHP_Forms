function PHP_Forms_isComplete(form, error_message) {
	var checkboxesAndRadios = {}; // empty associative array holding names and number of selections made
	if (typeof(form) !== 'undefined' && typeof(error_message) !== 'undefined') {
		var requiredClass = /(^|\s)required(\s|$)/; // matches required class name
		var emptyValue = /^\s*$/; // matches empty values
		var elements = form.elements;
		for (var i = 0; i < elements.length; i++) {
			if (requiredClass.test(elements[i].className)) { // check elements with required-class only
				if (typeof(elements[i].type) !== 'undefined' && (elements[i].type == 'checkbox' || elements[i].type == 'radio')) { // checkbox or radio field (only 1 selection necessary)
					if (typeof(checkboxesAndRadios[elements[i].name]) === 'undefined') {
						checkboxesAndRadios[elements[i].name] = 0; // init selection count with 0
					}
					if (elements[i].checked) {
						checkboxesAndRadios[elements[i].name]++; // count selected options for this group
					}
				}
				else if (typeof(elements[i].value) !== 'undefined') { // other input field (value required)
					if (emptyValue.test(elements[i].value)) { // if field is empty
						if (error_message !== '') {
							alert(error_message); // show error message if set
						}
						return false; // quit validation
					}
				}
			}
		}
		for (var field_name in checkboxesAndRadios) { // loop through all checkbox and radio groups
			if (checkboxesAndRadios[field_name] == 0) { // if no entry has been selected
				if (error_message !== '') {
					alert(error_message); // show error message if set
				}
				return false; // quit validation
			}
		}
	}
	return true;
}