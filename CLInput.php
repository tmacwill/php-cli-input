<?php

    class CLInput {
        private $window;
        private $offset;

        public function __construct($title = '', $subtitle = '') {
            ncurses_init();
            ncurses_noecho();
            $this->window = ncurses_newwin(0, 0, 0, 0);
            $this->offset = 0;

            // display title and subtitle
            if ($title)
                ncurses_mvaddstr($this->offset++, 0, $title);
            if ($subtitle)
                ncurses_mvaddstr($this->offset++, 0, $subtitle);

            // newline
            ncurses_mvaddstr($this->offset, 0, '');
            ncurses_refresh();
        }

        public function done() {
            ncurses_end();
        }

        public function password($prefix = '', $validate = null) {
            return $this->input($prefix, $validate, '*');
        }

        private function render_menu($options, $selected_index = 0) {
            // iterate through options in menu
            $n = count($options);
            for ($i = 0; $i < $n; $i++) {
                // highlight current option
                if ($i == $selected_index) {
                    ncurses_attron(NCURSES_A_REVERSE);
                    ncurses_mvaddstr($i + $this->offset, 0, $options[$i]);
                    ncurses_attroff(NCURSES_A_REVERSE);
                }

                // if not highlighted, display normally
                else
                    ncurses_mvaddstr($i + $this->offset, 0, $options[$i]);
            }
        }

        public function select($options, $prefix = '') {
            // start on a new line and hide cursor
            $this->offset++;
            ncurses_curs_set(0);

            // display prefix to prompt
            if ($prefix) {
                $prefix .= ': ';
                ncurses_mvaddstr($this->offset++, 0, $prefix);
                ncurses_mvaddstr($this->offset, 0, '---');
            }

            // render initial selection menu
            $n = count($options);
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

        public function text($prefix = '', $validate = null, $display_character = false) {
            if ($prefix)
                $prefix .= ': ';

            // loop until inputted text passes validation
            do {
                // start on a new line
                $result = '';
                $this->offset++;

                // display prefix to prompt
                if ($prefix)
                    ncurses_mvaddstr($this->offset, 0, $prefix);

                // loop until user presses enter
                $index = strlen($prefix);
                while (!in_array($key = ncurses_getch(), array(13, 10))) {
                    // backspace, so remove last character from result and display
                    if ($key == NCURSES_KEY_BACKSPACE) {
                        $result = substr($result, 0, -1);
                        ncurses_mvaddstr($this->offset, --$index, ' ');
                        ncurses_mvaddstr($this->offset, $index, '');
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
            } 
            while ($validate !== null && !call_user_func_array($validate, array($result)));

            return $result;
        }
    }

    // create ncurses window
    $menu = new CLInput('This is CS50 Submit', 'Press Ctrl-C to quit');
    $email = $menu->text('Email', function ($result) { return $result == 'tommy'; });
    //$password = $menu->password('Password');
    //$project = $menu->select(array('pset1', 'final project', 'project2'), 'Project to submit');
    //$project2 = $menu->select(array('pset1', 'final project', 'project2'), 'Project to submit');
    $menu->done();
?>
