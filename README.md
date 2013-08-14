# PHP_Forms

PHP library for creating HTML forms fast and easily with PHP. Takes care of all recurring and annoying tasks.

## Using the library

```
<?php include 'PHP_Forms.php'; ?>
<link rel="stylesheet" type="text/css" media="all" href="PHP_Forms.css" />
<script type="text/javascript" src="PHP_Forms.js"></script>
```

## Example

```
<?php
$form = new PHP_Forms('Bitte füllen Sie alle Pflichtfelder (*) aus!', true);
$form->setMethod(PHP_Forms::METHOD_POST);
$form->setTarget('');
$form->setHeader('<p><strong>Custom header text ...</strong></p>');
$form->setFooter('<p><strong>Custom footer text ...</strong></p>');

$section_name = $form->addSection('Your name:');
$section_name->addInputText('name', 'name', true, 'Enter your name ...');
$section_name->addInputText('surname', 'surname', true, 'Enter your surname ...');

$section_about = $form->addSection('About you:');

$field_gender = $section_about->addInputRadioGroup('gender', 'Your gender', false, 'female');
$field_gender->addOption('I am male', 'male');
$field_gender->addOption('I am female', 'female');

$field_age = $section_about->addSelect('age', 'Your age:', true);
for ($y = 12; $y < 99; $y++) {
	$field_age->addOption('I am '.$y.' years old', $y);
}

$form->showAntiSpam($section_about);
$section_about->addInputSubmit('Submit form', 'button_submit');

echo $form->getHTML();
?>
```

## Checking responses

```
<?php
if (PHP_Forms::hasResponse(PHP_Forms::METHOD_POST)) {
	if (PHP_Forms::Response_isValid()) {
		$name = PHP_Forms::Response_getString('name');
		$surname = PHP_Forms::Response_getString('surname');
		$gender = PHP_Forms::Response_getString('gender');
		$age = PHP_Forms::Response_getInt('age');
		echo '<p>'.$name.' '.$surname.' ('.$gender.') is '.$age.' years old.</p>';
	}
	else {
		echo '<h1>Something went wrong</h1>';
		echo '<p>Please fill out all required fields (*) and enter the correct solution to the arithmetic problem!</p>';
	}
}
?>
```

## Sending emails

```
<?php
$mail = new PHP_Forms_Mail('sender@example.org', 'John Doe', 'Sample subject');
$mail->addRecipient('jane@example.org');
$mail->addRecipient('ben@example.org', true);
$mail->addLine('Dear Jane');
$mail->addLine('');
$mail->addLine('This is my message to you.');
$mail->addLine('');
$mail->addLine('John');
$mail->send();
?>
```
