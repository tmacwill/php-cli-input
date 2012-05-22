<?php

require_once 'CLInput.php';

// create a new input object
$input = new CLInput('CLInput demo', 'Press Ctrl-C to quit');

// prompt for an email
$email = $input->email();

// prompt for a password
$password = $input->password();

// prompt for an option
$option = $input->select(array(
    'the first option',
    'another option',
    'or maybe you want this one?'
), 'Select an option');

// prompt for some text
$tommy = $input->text('Input the word tommy', function($result) {
    return $result == 'tommy';
}, 'I told you to input the word tommy!');

// exit input mode
$input->done();

// output what the user typed in
var_dump($email);
var_dump($password);
var_dump($option);
var_dump($tommy);

?>
