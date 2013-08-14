<?php

/**
 * PHP_Forms
 * + support for all important form elements:
 *   + text input
 *   + hidden input
 *   + checkbox input
 *   + radio input
 *   + textarea
 *   + select list
 *   + submit button
 * + automatic client-side verification (JavaScript)
 * + automatic server-side validation (PHP)
 * + anti-spam protection (optional)
 * + valid and standards-compliant HTML/CSS
 * + responsive layout
 * + semantic HTML
 * + intuitive email wrapper
 * + open-source
 */
class PHP_Forms {

	const METHOD_POST = 0; // identifier for the POST method
	const METHOD_GET = 1; // identifier for the GET method
	const INPUT_NAME_FORM_ID = 'form_id'; // field containing a Base64-encoded JSON array where [0] = anti-spam answer [1] = array of required fields' names
	const INPUT_NAME_ANTI_SPAM = 'anti_spam'; // field where users type their anti-spam answer
	const PREFIX = 'PHP_Forms_'; // prefix to apply to all field names in order to prevent collisions with other components

	protected $incomplete_message = ''; // the custom error message to show if any required field is missing or the anti-spam answer is wrong
	protected $spam_protect_question = ''; // holds the anti-spam question for this form (if enabled) or an empty string
	protected $spam_protect_answer = ''; // holds the anti-spam answer for this form (if enabled) or an empty string
	protected $sections = array(); // holds the list of all sections in this form
	protected $header = ''; // custom HTML header of this form
	protected $footer = ''; // custom HTML footer of this form
	protected $submit_method;
	protected $submit_target = '';
	
	/**
	 * Creates a new HTML form
	 *
	 * @param String $incompleteMessage the message to show if any required field is missing or the anti-spam question has not been answered correctly
	 * @param boolean $antiSpamEnabled whether the anti-spam protection should be enabled or not (if enabled, the security question must later be shown with showAntiSpam())
	 */
	public function __construct($incompleteMessage, $antiSpamEnabled) {
		$this->incomplete_message = $incompleteMessage;
		if ($antiSpamEnabled) {
			$number1 = mt_rand(1, 15);
			$number2 = mt_rand(1, 15);
			$this->spam_protect_question = $number1.' + '.$number2.' = ?';
			$this->spam_protect_answer = $number1+$number2;
		}
		else {
			$this->spam_protect_question = '';
			$this->spam_protect_answer = '';
		}
	}
	
	/**
	 * Sets whether anti-spam protection should be enabled (true) or not (false)
	 */
	public function showAntiSpam($section) {
		if (is_a($section, 'PHP_Forms_Section')) {
			if (isset($this->spam_protect_question) && $this->spam_protect_question != '') {
				$section->addInputTextInternal(self::INPUT_NAME_ANTI_SPAM, $this->spam_protect_question, true, true);
			}
			else {
				throw new Exception('You must set antiSpamEnabled to true in the constructor if you want to use showAntiSpam()');
			}
		}
		else {
			throw new Exception('You must pass a valid PHP_Forms_Section instance to showAntiSpam()');
		}
	}
	
	/**
	 * Checks if the response to any form was sent to the current page
	 */
	public static function hasResponse($submitMethod) {
		if ($submitMethod == self::METHOD_GET) {
			return isset($_GET) && is_array($_GET) && count($_GET) > 0;
		}
		else if ($submitMethod == self::METHOD_POST) {
			return isset($_POST) && is_array($_POST) && count($_POST) > 0;
		}
		else {
			throw new Exception('Unknown submit method: '.$submitMethod);
		}
	}
	
	/**
	 * Checks if the GET or POST response is valid (server-side validation and anti-spam protection)
	 */
	public static function Response_isValid() {
		$internalFormJSON = self::Response_getString(PHP_Forms::INPUT_NAME_FORM_ID); // get internal form data that have been passed in a hidden field
		$internalFormData = json_decode(base64_decode($internalFormJSON));
		if (!isset($internalFormData[1])) {
			$internalFormData[1] = array();
		}
		// ANTI-SPAM PROTECTION BEGIN
		if (isset($internalFormData[0]) && $internalFormData[0] != '') { // if anti-spam is enabled
			if (self::Response_getString(self::INPUT_NAME_ANTI_SPAM) != $internalFormData[0]) { // if user did not answer correctly at anti-spam check
				return false; // response is invalid
			}
		}
		// ANTI-SPAM PROTECTION END
		// SERVER-SIDE VALIDATION BEGIN
		foreach ($internalFormData[1] as $requiredField) { // for all required fields
			if (self::Response_getString($requiredField) == '') { // if input was empty
				return false; // response is invalid
			}
		}
		// SERVER-SIDE VALIDATION END
		return true;
	}
	
	/**
	 * Gets a text string from the GET or POST response
	 */
	public static function Response_getString($name) {
		return isset($_REQUEST[self::PREFIX.$name]) ? trim($_REQUEST[self::PREFIX.$name]) : '';
	}
	
	/**
	 * Gets an integer number from the GET or POST response
	 */
	public static function Response_getInt($name) {
		return intval(self::Response_getString($name));
	}
	
	/**
	 * Sets a custom HTML source code that may be displayed above the form
	 */
	public function setHeader($html) {
		$this->header = $html;
	}
	
	/**
	 * Sets a custom HTML source code that may be displayed below the form
	 */
	public function setFooter($html) {
		$this->footer = $html;
	}
	
	/**
	 * Sets the transfer method of this form (may be GET or POST)
	 */
	public function setMethod($method) {
		$this->submit_method = intval($method);
	}
	
	/**
	 * Sets the target URL of this form (the page that all data will be sent to)
	 */
	public function setTarget($url) {
		$this->submit_target = $url;
	}
	
	/**
	 * Adds a new section with the given display title to this form (no headline displayed if title is omitted)
	 */
	public function addSection($sectionTitle = '') {
		$section = new PHP_Forms_Section($sectionTitle);
		$this->sections[] = $section;
		return $section;
	}
	
	protected function getMethod() {
		if ($this->submit_method == self::METHOD_GET) {
			return 'get';
		}
		else if ($this->submit_method == self::METHOD_POST) {
			return 'post';
		}
		else {
			throw new Exception('Unknown submit method: '.$this->submit_method);
		}
	}
	
	/**
	 * Returns the complete HTML source code for the form, its sections and elements
	 */
	public function getHTML() {
		// CONSTRUCT ARRAY OF INTERNAL FORM DATA BEGIN
		$internalFormData = array();
		$internalFormData[0] = $this->spam_protect_answer; // entry [0] holds the anti-spam answer
		$internalFormData[1] = array(); // entry [1] holds the array of required fields' names
		foreach ($this->sections as $section) { // in each section
			$elements = $section->getElements();
			foreach ($elements as $element) { // loop through all elements
				if ($element->isRequired()) { // for required elements
					$internalFormData[1][] = $element->getName(); // add name to list
				}
			}
		}
		// CONSTRUCT LIST OF REQUIRED FIELDS END
		$out = $this->header;
		$out .= '<form class="php_forms" action="'.htmlspecialchars($this->submit_target).'" method="'.$this->getMethod().'" accept-charset="utf-8" onsubmit="return '.self::PREFIX.'isComplete(this, \''.htmlspecialchars($this->incomplete_message).'\');">';
		foreach ($this->sections as $section) {
			if (isset($internalFormData)) { // if array of internal form data has not been used yet (i.e. not added to any section)
				$section->addInputHiddenInternal(self::INPUT_NAME_FORM_ID, base64_encode(json_encode($internalFormData)), true);
				$internalFormData = NULL; // mark list as used
			}
			$out .= $section->getHTML();
		}
		// ADD LIST OF REQUIRED FIELDS TO HIDDEN FIELD BEGIN
		if (isset($internalFormData)) { // if list of required fields could not be added to any section
			throw new Exception('No PHP_Forms_Section instance found to apply the list of required fields to');
		}
		// ADD LIST OF REQUIRED FIELDS TO HIDDEN FIELD END
		$out .= '</form>';
		$out .= $this->footer;
		return $out;
	}

}

/**
 * Section that can be used to group related form elements, will be shown with the custom section title
 */
class PHP_Forms_Section {

	protected $title;
	protected $elements;

	/**
	 * Creates a new section that groups related form elements, will be displayed with its custom title
	 */
	public function __construct($title) {
		$this->title = $title;
		$this->elements = array();
	}
	
	/**
	 * Adds a new select field (drop-down list) to this section with a given internal name and display title, may be are required field (optionally)
	 */
	public function addSelect($name, $title, $is_required = false, $defaultValue = '') {
		if ($name == PHP_Forms::INPUT_NAME_ANTI_SPAM || $name == PHP_Forms::INPUT_NAME_FORM_ID) {
			throw new Exception('You may not add custom elements with name \''.PHP_Forms::INPUT_NAME_ANTI_SPAM.'\' or \''.PHP_Forms::INPUT_NAME_FORM_ID.'\'');
		}
		$element = new PHP_Forms_Select(PHP_Forms::PREFIX.$name, $title, $is_required, $defaultValue);
		$this->elements[] = $element;
		return $element;
	}
	
	/**
	 * Adds a new select field (drop-down list) to this section with a given internal name and display title, may be are required field (optionally)
	 */
	public function addInputCheckboxGroup($name, $title, $is_required = false, $defaultValue = '') {
		if ($name == PHP_Forms::INPUT_NAME_ANTI_SPAM || $name == PHP_Forms::INPUT_NAME_FORM_ID) {
			throw new Exception('You may not add custom elements with name \''.PHP_Forms::INPUT_NAME_ANTI_SPAM.'\' or \''.PHP_Forms::INPUT_NAME_FORM_ID.'\'');
		}
		$element = new PHP_Forms_InputCheckboxGroup(PHP_Forms::PREFIX.$name, $title, $is_required, $defaultValue);
		$this->elements[] = $element;
		return $element;
	}
	
	/**
	 * Adds a new select field (drop-down list) to this section with a given internal name and display title, may be are required field (optionally)
	 */
	public function addInputRadioGroup($name, $title, $is_required = false, $defaultValue = '') {
		if ($name == PHP_Forms::INPUT_NAME_ANTI_SPAM || $name == PHP_Forms::INPUT_NAME_FORM_ID) {
			throw new Exception('You may not add custom elements with name \''.PHP_Forms::INPUT_NAME_ANTI_SPAM.'\' or \''.PHP_Forms::INPUT_NAME_FORM_ID.'\'');
		}
		$element = new PHP_Forms_InputRadioGroup(PHP_Forms::PREFIX.$name, $title, $is_required, $defaultValue);
		$this->elements[] = $element;
		return $element;
	}

	/** Adds a new text input (direct access to this method is not needed usually, use addInputText() instead */
	public function addInputTextInternal($name, $title, $is_required, $is_system, $defaultValue = '') {
		if (($name == PHP_Forms::INPUT_NAME_ANTI_SPAM || $name == PHP_Forms::INPUT_NAME_FORM_ID) && !$is_system) {
			throw new Exception('You may not add custom elements with name \''.PHP_Forms::INPUT_NAME_ANTI_SPAM.'\' or \''.PHP_Forms::INPUT_NAME_FORM_ID.'\'');
		}
		$element = new PHP_Forms_InputText(PHP_Forms::PREFIX.$name, $title, $is_required, $defaultValue);
		$this->elements[] = $element;
		return $element;
	}
	
	/**
	 * Adds a new text input to this section with a given internal name and display title, may be are required field (optionally)
	 */
	public function addInputText($name, $title, $is_required = false, $defaultValue = '') {
		return self::addInputTextInternal($name, $title, $is_required, false, $defaultValue);
	}
	
	/** Adds a new hidden input (direct access to this method is not needed usually, use addInputHidden() instead */
	public function addInputHiddenInternal($name, $value, $is_system) {
		if (($name == PHP_Forms::INPUT_NAME_ANTI_SPAM || $name == PHP_Forms::INPUT_NAME_FORM_ID) && !$is_system) {
			throw new Exception('You may not add custom elements with name \''.PHP_Forms::INPUT_NAME_ANTI_SPAM.'\' or \''.PHP_Forms::INPUT_NAME_FORM_ID.'\'');
		}
		$element = new PHP_Forms_InputHidden(PHP_Forms::PREFIX.$name, $value);
		$this->elements[] = $element;
		return $element;
	}
	
	/**
	 * Adds a new hidden input to this section with a given internal name and the corresponding value
	 */
	public function addInputHidden($name, $value) {
		return self::addInputHiddenInternal($name, $value, false);
	}
	
	/**
	 * Adds a new textarea to this section with a given internal name and display title, may be are required field (optionally) and may have a custom size (optionally)
	 */
	public function addTextarea($name, $title, $is_required = false, $size = 0, $defaultValue = '') {
		if ($name == PHP_Forms::INPUT_NAME_ANTI_SPAM || $name == PHP_Forms::INPUT_NAME_FORM_ID) {
			throw new Exception('You may not add custom elements with name \''.PHP_Forms::INPUT_NAME_ANTI_SPAM.'\' or \''.PHP_Forms::INPUT_NAME_FORM_ID.'\'');
		}
		$element = new PHP_Forms_Textarea(PHP_Forms::PREFIX.$name, $title, $is_required, $size, $defaultValue);
		$this->elements[] = $element;
		return $element;
	}
	
	/**
	 * Adds a new submit button to this section with a given display title, internal name (may be aligned to the left)
	 */
	public function addInputSubmit($title, $name = 'submit_button', $align_left = false) {
		if ($name == PHP_Forms::INPUT_NAME_ANTI_SPAM || $name == PHP_Forms::INPUT_NAME_FORM_ID) {
			throw new Exception('You may not add custom elements with name \''.PHP_Forms::INPUT_NAME_ANTI_SPAM.'\' or \''.PHP_Forms::INPUT_NAME_FORM_ID.'\'');
		}
		$element = new PHP_Forms_InputSubmit(PHP_Forms::PREFIX.$name, $title, $align_left);
		$this->elements[] = $element;
		return $element;
	}
	
	/**
	 * Returns the HTML source code for this section and its form elements
	 */
	public function getHTML() {
		$out = '<fieldset>';
		if (isset($this->title) && $this->title != '') {
			$out .= '<legend>'.htmlspecialchars($this->title).'</legend>';
		}
		$out .= '<ol>';
		foreach ($this->elements as $element) {
			$out .= $element->getHTML();
		}
		$out .= '</ol>';
		$out .= '</fieldset>';
		return $out;
	}
	
	/** Returns the list of all elements in this section (direct access to this method is not needed usually) */
	public function getElements() {
		return $this->elements;
	}

}

/**
 * Superclass for all form elements (may not be accessed directly)
 */
class PHP_Forms_Element {

	protected $name;
	protected $title;
	protected $is_required;
	
	protected function __construct($name, $title, $is_required = false) {
		$this->name = $name;
		$this->title = $title;
		$this->is_required = $is_required;
	}
	
	protected function getTitle() {
		return htmlspecialchars($this->title).($this->is_required ? ' <em>*</em>' : '');
	}

	/** Returns whether this field is required or not (direct access to this method is not needed usually) */
	public function isRequired() {
		return $this->is_required;
	}
	
	/**
	 * Returns the internal name of this field (direct access to this method is not needed usually)
	 *
	 * @param boolean $includePrefix whether to include the global field name prefix (true) or not (false)
	 */
	public function getName($includePrefix = false) {
		if ($includePrefix) {
			return $this->name;
		}
		else {
			return mb_substr($this->name, mb_strlen(PHP_Forms::PREFIX));
		}
	}

}

/**
 * Select field (drop-down list)
 */
class PHP_Forms_Select extends PHP_Forms_Element {

	protected $options;
	protected $defaultValue;
	
	/**
	 * Creates a select field (drop-down list) with the given name and display title (may be a required field)
	 */
	public function __construct($name, $title, $is_required, $defaultValue = '') {
		parent::__construct($name, $title, $is_required);
		$this->options = array();
		$this->defaultValue = $defaultValue;
	}
	
	/**
	 * Adds a new option to the select field with the given display title and internal name (optionally)
	 */
	public function addOption($title, $name = '') {
		if ($name == '') {
			$name = $title;
		}
		$this->options[$name] = $title;
	}
	
	/**
	 * Returns the HTML source code for this form element
	 */
	public function getHTML() {
		$out = '<li>';
		$out .= '<label for="'.htmlspecialchars($this->name).'">'.parent::getTitle().'</label>';
		$out .= '<select'.($this->is_required ? ' class="required"' : '').' id="'.htmlspecialchars($this->name).'" name="'.htmlspecialchars($this->name).'" size="1">';
		foreach ($this->options as $option_name => $option_title) {
			$out .= '<option value="'.htmlspecialchars($option_name).'"';
			if ($option_name == strval($this->defaultValue)) {
				$out .= ' selected="selected"';
			}
			$out .= '>'.htmlspecialchars($option_title).'</option>';		
		}
		$out .= '</select>';
		$out .= '</li>';
		return $out;
	}

}

/**
 * Group of checkboxes (inpur checkbox fields)
 */
class PHP_Forms_InputCheckboxGroup extends PHP_Forms_Element {

	protected $options;
	protected $defaultValue;
	
	/**
	 * Creates a group of checkboxes with the given name and display title (may be a required field)
	 */
	public function __construct($name, $title, $is_required, $defaultValue = '') {
		parent::__construct($name, $title, $is_required);
		$this->options = array();
		$this->defaultValue = $defaultValue;
	}
	
	/**
	 * Adds a new option to the checkbox group with the given display title and internal name (optionally)
	 */
	public function addOption($title, $name = '') {
		if ($name == '') {
			$name = $title;
		}
		$this->options[$name] = $title;
	}
	
	/**
	 * Returns the HTML source code for this form element
	 */
	public function getHTML() {
		$out = '';
		$counter = 0;
		foreach ($this->options as $option_name => $option_title) {
			$out .= '<li>';
			if ($counter == 0) {
				$out .= '<label class="groupEntry">'.parent::getTitle().'</label>';
			}
			else {
				$out .= '<label class="groupEntry">&nbsp;</label>';
			}
			$out .= '<input'.($this->is_required ? ' class="required"' : '').' type="checkbox"';
			if ($option_name == strval($this->defaultValue)) {
				$out .= ' checked="checked"';
			}
			$out .= ' name="'.htmlspecialchars($this->name).'" value="'.htmlspecialchars($option_name).'" /> '.htmlspecialchars($option_title);
			$out .= '</li>';
			$counter++;
		}
		return $out;
	}

}

/**
 * Group of radio fields (inpur radio fields)
 */
class PHP_Forms_InputRadioGroup extends PHP_Forms_Element {

	protected $options;
	protected $defaultValue;
	
	/**
	 * Creates a group of radio inputs with the given name and display title (may be a required field)
	 */
	public function __construct($name, $title, $is_required, $defaultValue = '') {
		parent::__construct($name, $title, $is_required);
		$this->options = array();
		$this->defaultValue = $defaultValue;
	}
	
	/**
	 * Adds a new option to the radio group with the given display title and internal name (optionally)
	 */
	public function addOption($title, $name = '') {
		if ($name == '') {
			$name = $title;
		}
		$this->options[$name] = $title;
	}
	
	/**
	 * Returns the HTML source code for this form element
	 */
	public function getHTML() {
		$out = '';
		$counter = 0;
		foreach ($this->options as $option_name => $option_title) {
			$out .= '<li>';
			if ($counter == 0) {
				$out .= '<label class="groupEntry">'.parent::getTitle().'</label>';
			}
			else {
				$out .= '<label class="groupEntry">&nbsp;</label>';
			}
			$out .= '<input'.($this->is_required ? ' class="required"' : '').' type="radio"';
			if ($option_name == strval($this->defaultValue)) {
				$out .= ' checked="checked"';
			}
			$out .= ' name="'.htmlspecialchars($this->name).'" value="'.htmlspecialchars($option_name).'" /> '.htmlspecialchars($option_title);
			$out .= '</li>';
			$counter++;
		}
		return $out;
	}

}

/**
 * Text input (for short pieces of text)
 */
class PHP_Forms_InputText extends PHP_Forms_Element {

	protected $defaultValue;
	
	/**
	 * Creates a new text input (for short pieces of text) with the given name and display title (may be a required field)
	 */
	public function __construct($name, $title, $is_required, $defaultValue = '') {
		parent::__construct($name, $title, $is_required);
		$this->defaultValue = $defaultValue;
	}
	
	/**
	 * Returns the HTML source code for this form element
	 */
	public function getHTML() {
		$out = '<li>';
		$out .= '<label for="'.htmlspecialchars($this->name).'">'.parent::getTitle().'</label>';
		$out .= '<input'.($this->is_required ? ' class="required"' : '').' id="'.htmlspecialchars($this->name).'" name="'.htmlspecialchars($this->name).'" type="text" value="'.htmlspecialchars($this->defaultValue).'" />';
		$out .= '</li>';
		return $out;
	}

}

/**
 * Hidden input (for sending predefined texts along with the form content)
 */
class PHP_Forms_InputHidden extends PHP_Forms_Element {

	protected $value;
	
	/**
	 * Creates a new hidden input (for sending predefined texts along with the form content) with the given name and value
	 */
	public function __construct($name, $value) {
		parent::__construct($name, '', false);
		$this->value = $value;
	}
	
	/**
	 * Returns the HTML source code for this form element
	 */
	public function getHTML() {
		$out = '<li style="display:none;">';
		$out .= '<input id="'.htmlspecialchars($this->name).'" name="'.htmlspecialchars($this->name).'" value="'.htmlspecialchars($this->value).'" type="hidden" />';
		$out .= '</li>';
		return $out;
	}

}

/**
 * Textarea (for longer pieces of text)
 */
class PHP_Forms_Textarea extends PHP_Forms_Element {

	const SIZE_SMALL = 0;
	const SIZE_MEDIUM = 1;
	const SIZE_LARGE = 2;
	
	protected $size;
	protected $defaultValue;
	
	/**
	 * Creates a new textarea (for longer pieces of text) with the given name, display title and size (may be a required field)
	 */
	public function __construct($name, $title, $is_required, $size, $defaultValue = '') {
		parent::__construct($name, $title, $is_required);
		$this->size = intval($size);
		$this->defaultValue = $defaultValue;
	}
	
	protected function getSizeClass() {
		if ($this->size == self::SIZE_LARGE) {
			return 'large';
		}
		if ($this->size == self::SIZE_MEDIUM) {
			return 'medium';
		}
		else {
			return 'small';
		}
	}
	
	/**
	 * Returns the HTML source code for this form element
	 */
	public function getHTML() {
		$out = '<li>';
		$out .= '<label for="'.htmlspecialchars($this->name).'">'.parent::getTitle().'</label>';
		$out .= '<textarea class="'.$this->getSizeClass().($this->is_required ? ' required' : '').'" id="'.htmlspecialchars($this->name).'" name="'.htmlspecialchars($this->name).'" rows="10" cols="10">'.htmlspecialchars($this->defaultValue).'</textarea>';
		$out .= '</li>';
		return $out;
	}

}

/**
 * Submit button
 */
class PHP_Forms_InputSubmit extends PHP_Forms_Element {

	protected $button_text;
	protected $align_left;
	
	/**
	 * Creates a new submit button with the given name and display title (may be aligned to the left)
	 */
	public function __construct($name, $title, $align_left) {
		parent::__construct($name, '', false);
		$this->button_text = $title;
		$this->align_left = $align_left;
	}

	/**
	 * Returns the HTML source code for this form element
	 */
	public function getHTML() {
		$out = '<li>';
		if (!$this->align_left) {
			$out .= '<label for="'.htmlspecialchars($this->name).'">'.parent::getTitle().'</label>';
		}
		$out .= '<input id="'.htmlspecialchars($this->name).'" name="'.htmlspecialchars($this->name).'" value="'.htmlspecialchars($this->button_text).'" type="submit" />';
		$out .= '</li>';
		return $out;
	}

}

/**
 * Sends emails (plain text or HTML) simply and conveniently
 */
class PHP_Forms_Mail {

	protected $lines = array();
	protected $recipientsTo = array(); // list of visible recipients (To)
	protected $recipientsBCC = array(); // list of invisible recipients (BCC)
	protected $fromMail = '';
	protected $fromName = '';
	protected $subject = '';
	protected $is_html = false;
	
	/**
	 * Creates a new email that may be sent later
	 */
	public function __construct($fromMail, $fromName, $subject, $is_html = false) {
		$this->fromMail = trim($fromMail);
		$this->fromName = trim($fromName);
		$this->subject = trim($subject);
		$this->is_html = $is_html;
	}

	/**
	 * Adds a new recipient for this message (may be an invisible BCC, optionally)
	 */
	public function addRecipient($mail, $isBCC = false) {
		if ($isBCC) {
			$this->recipientsBCC[] = $mail;
		}
		else {
			$this->recipientsTo[] = $mail;
		}
	}
	
	/**
	 * Adds a new line with arbitrary content to the message body
	 */
	public function addLine($text) {
		$this->lines[] = $text;
	}
	
	/**
	 * Sends the email that has already been prepared (recipient, sender and subject must be set)
	 */
	public function send() {
		if (count($this->recipientsTo) <= 0 && count($this->recipientsBCC) <= 0) {
			throw new Exception('There must be at least one recipient (addRecipient)');
		}
		elseif (!isset($this->fromMail) || $this->fromMail == '') {
			throw new Exception('The sender\'s mail (fromMail) may not be empty');
		}
		elseif (!isset($this->subject) || $this->subject == '') {
			throw new Exception('The subject may not be empty');
		}
		else {
			$from_string = (isset($this->fromName) && $this->fromName != '') ? $this->fromName.' <'.$this->fromMail.'>' : $this->fromMail;
			$header = 'From: '.$from_string."\r\n";
			if (count($this->recipientsBCC) > 0) {
				$header .= 'Bcc: '.implode(', ', $this->recipientsBCC)."\r\n";
			}
			if ($this->is_html) {
				$header .= 'Content-type: text/html; charset=utf-8';
			}
			else {
				$header .= 'Content-type: text/plain; charset=utf-8';
			}
			mail(implode(', ', $this->recipientsTo), $this->subject, self::getBody(), $header);
		}
	}
	
	protected function getBody() {
		return implode("\n", $this->lines);
	}
	
	/**
	 * For debugging only: Returns the message (body) of the email to send
	 */
	public function getText() {
		return self::getBody();
	}

}

?>