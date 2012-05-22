CLInput
---

## Installation

CLInput requires the ncurses library for PHP. It's available here http://pecl.php.net/package/ncurses or via your package manager.

## Usage

Prompt for an email address:

    $email = $input->email();

Prompt for a password:

    $password = $input->password();

Prompt for text that matches a validation function

    $tommy = $input->text('Input the word tommy', function($result) {
        return $result == 'tommy';
    }, 'I told you to input tommy');

Allow the user to select an option from a list:

    $option = $input->select(array(
        'the first option',
        'another option',
        'or maybe you want this one?'
    ));

Check out example.php for a complete example.
