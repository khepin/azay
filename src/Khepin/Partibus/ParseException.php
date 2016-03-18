<?php
namespace Khepin\Partibus;

use \Exception;

class ParseException extends Exception {

    public function __construct(Input $input) {
        $parsed_input = substr($input->input, 0, $input->position);
        $lines = explode("\n", $parsed_input);
        $line_number = count($lines);
        $col_number = strlen($lines[$line_number - 1]);
        $surrounding_input = substr(
            $input->input,
            max(0, $input->position - 25),
            min(strlen($input->input), $input->position + 25)
        );

        parent::__construct(sprintf('Parse Error at %d:%d, near: %s', $line_number, $col_number, $surrounding_input));
    }
}