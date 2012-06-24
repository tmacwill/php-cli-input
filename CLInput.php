<?php

/**
 * Command-line input utilities
 * Tommy MacWilliam <tmacwilliam@cs.harvard.edu>
 *
 */
class CLInput {
    // ncurses window object
    private $window;
    // current line
    private $offset;

    /**
     * Initialize a new input object
     *
     * @param $title Text to be displayed on first line 
     * @param $subtitle Text to be displayed on second line 
     *
     */
    public function __construct($title = '', $subtitle = '') {
        // initialize ncurses
        ncurses_init();
        ncurses_noecho();
        $this->window = ncurses_newwin(0, 0, 0, 0);
        $this->offset = 0;

        // display title and subtitle
        if ($title)
            ncurses_mvaddstr($this->offset++, 0, $title);
        if ($subtitle)
            ncurses_mvaddstr($this->offset++, 0, $subtitle);

        // display newline
        ncurses_mvaddstr($this->offset, 0, '');
        ncurses_refresh();
    }

    /**
     * Make sure that a given number of lines will fit on the screen and claer if not
     *
     * @param $required_lines Number of lines that will be displayed
     *
     */
    private function check_bounds($required_lines = 1) {
        ncurses_getmaxyx($this->window, $y, $x);
        if ($this->offset >= $y - $required_lines) {
            $this->offset = 0;
            ncurses_clear();
        }
    }

    /**
     * Finish getting input from the user
     * THIS ABSOLUTELY MUST BE CALLED AT SOME POINT BEFORE YOUR PROGRAM TERMINATES
     * SERIOUSLY
     *
     */
    public function done() {
        ncurses_refresh();
        ncurses_end();
        usleep(300000);
    }

    /**
     * Prompt for an email address
     *
     * @param $prompt Text to display before user input
     * @param $message Message to display on invalid input
     *
     */
    public function email($prompt = 'Email', $message = 'Please enter a valid email address.') {
        return $this->text($prompt, function($result) { 
            return filter_var($result, FILTER_VALIDATE_EMAIL); 
        }, $message);
    }

    /**
     * Prompt for a floating-point decimal
     *
     * @param $prompt Text to display before user input
     * @param $message Message to display on invalid input
     *
     */
    public function float($prompt = 'Float', $message = 'Please enter a floating-point decimal.') {
        return $this->text($prompt, function($result) { 
            return filter_var($result, FILTER_VALIDATE_FLOAT); 
        }, $message);
    }

    /**
     * Prompt for an integer
     *
     * @param $prompt Text to display before user input
     * @param $message Message to display on invalid input
     *
     */
    public function integer($prompt = 'Integer', $message = 'Please enter an integer.') {
        return $this->text($prompt, function($result) { 
            return filter_var($result, FILTER_VALIDATE_INT); 
        }, $message);
    }

    /**
     * Prompt for a password
     *
     * @param $prompt Text to display before user input
     * @param $message Message to display on invalid input
     *
     */
    public function password($prompt = 'Password', $validate = null, $message = '') {
        return $this->text($prompt, $validate, $message, '*');
    }

    /**
     * Print a line of text 
     *
     */
    public function println($text) {
        $this->check_bounds();
        ncurses_mvaddstr($this->offset++, 0, $text);
    }

    /**
     * Render the selection menu, highlighting the current choice
     *
     * @param $options Array of options to be displayed in the menu
     * @param $selected_index Index of selected option
     *
     */
    private function render_menu($options, $selected_index = 0) {
        // determine how many options to display
        $n = count($options);
        $start = 0;

        // if menu is too large for the screen, then only display items that will fit
        ncurses_getmaxyx($this->window, $y, $x);
        if ($y < ($n + 2)) {
            // number of choices to be displayed is the screen hight minus the title offset
            $n = $y - 2;

            // start is a screen height away from the selection plus 3 for 1-indexing and height of selection prompt
            $start = $selected_index - $y + 3;
            if ($start < 0)
                $start = 0;
        }

        // display menu options
        for ($i = 0; $i < $n; $i++) {
            // index into options array depends on the current scroll position
            $index = $i + $start;

            // determine difference between length of option and terminal width
            $display_string = $options[$index];
            $padding = $x - strlen($options[$index]);

            // string is smaller than terminal, so pad with spaces
            if ($padding > 0)
                for ($j = 0; $j < $padding; $j++)
                    $display_string .= ' ';

            // string is larger than terminal, so cut off with ellipsis
            else if ($padding < 0)
                $display_string = substr($display_string, 0, $x - 3) . '...';

            // highlight current option
            if ($index == $selected_index) {
                ncurses_attron(NCURSES_A_REVERSE);
                ncurses_mvaddstr($i + $this->offset, 0, $display_string);
                ncurses_attroff(NCURSES_A_REVERSE);
            }

            // if not highlighted, display normally
            else
                ncurses_mvaddstr($i + $this->offset, 0, $display_string);
        }

        ncurses_refresh();
    }

    /**
     * Render a menu from which a user can select one option
     *
     * @param $options Array of options user can choose from
     * @param $prompt Text to display above the menu
     *
     */
    public function select($options, $prompt = 'Select an option') {
        // start on a new line and hide cursor
        $n = count($options);
        $this->offset += 2;
        ncurses_curs_set(0);
        $this->check_bounds($n + 2);

        // display prompt
        if ($prompt) {
            $prompt .= ': ';
            ncurses_mvaddstr($this->offset++, 0, $prompt);
            ncurses_mvaddstr($this->offset, 0, '---');
        }

        // render initial selection menu
        $this->offset++;
        $this->render_menu($options);

        // loop until user presses enter or space
        $selected_index = 0;
        while (!in_array($key = ncurses_getch(), array(13, 32))) {
            // move selection
            if ($key == NCURSES_KEY_UP)
                $selected_index--;
            else if ($key == NCURSES_KEY_DOWN)
                $selected_index++;

            // wrap around selection
            if ($selected_index < 0)
                $selected_index = $n - 1;
            else if ($selected_index > $n - 1)
                $selected_index = 0;

            // re-render menu with new item selected
            $this->render_menu($options, $selected_index);
        }

        // return cursor to normal visibility
        ncurses_curs_set(1);

        // take into account size of menu
        $this->offset += $n;

        return $selected_index;
    }

    /**
     * Prompt the user for a line of text
     * 
     * @param $prompt Text to display before user input
     * @param $validate Function that takes as an argument the user input and returns if it is valid
     * @param $message Message to display on invalid input
     * @param $display_character If not false, then character to display in place of user's input (e.g., for passwords)
     *
     */
    public function text($prompt = '', $validate = null, $message = 'Invalid input.', $display_character = false) {
        // append colon to prompt
        if ($prompt)
            $prompt .= ': ';

        // make sure to only display the error message if we have already tried
        $attempted = false;

        // loop until inputted text passes validation
        do {
            // start on a new line
            $result = '';
            $this->offset++;
            $this->check_bounds();

            // display error message if user's input failed to validate
            if ($attempted)
                ncurses_mvaddstr($this->offset++, 0, $message);

            // display prompt
            if ($prompt)
                ncurses_mvaddstr($this->offset, 0, $prompt);

            // loop until user presses enter
            $index = strlen($prompt);
            $prompt_length = $index;
            while (!in_array($key = ncurses_getch(), array(13, 10))) {
                // backspace, so remove last character from result and display
                if ($key == NCURSES_KEY_BACKSPACE) {
                    if ($index <= $prompt_length) 
                        ncurses_mvaddstr($this->offset, $index, '');

                    else {
                        $result = substr($result, 0, -1);
                        ncurses_mvaddstr($this->offset, --$index, ' ');
                        ncurses_mvaddstr($this->offset, $index, '');
                    }
                }

                // character, so display and add to result
                else if (!in_array($key, array(NCURSES_KEY_LEFT, NCURSES_KEY_UP, 
                        NCURSES_KEY_RIGHT, NCURSES_KEY_DOWN))) {
                    $result .= chr($key);
                    ncurses_mvaddstr($this->offset, $index++, ($display_character) ? 
                        $display_character : chr($key));
                }

                ncurses_refresh();
            } 

            // if this input fails to validate, display error message
            $attempted = true;
        } 
        while ($validate !== null && !call_user_func_array($validate, array($result)));

        return $result;
    }
}

?>
