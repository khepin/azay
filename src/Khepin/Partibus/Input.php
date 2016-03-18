<?php
namespace Khepin\Partibus;

/**
 * Represents a parser's input as the parser keeps advancing through the parsing process.
 */
class Input {
    /**
     * The actual input string
     * @var string
     */
    public $input = '';

    /**
     * Current position in the input
     * @var integer
     */
    public $position = 0;

    /**
     * @param string $string
     */
    public function __construct($string = '') {
        $this->input = $string;
    }

    /**
     * Read the next n charcters from the input
     * @param  integer $i
     * @return string
     */
    public function read($i = 1) {
        $substr = substr($this->input, $this->position, $i);
        return $substr;
    }

    /**
     * Reads the reamining input. This is only useful for applying regex type parsers
     * since we don't know in advance what length of input they will consume.
     * @return string
     */
    public function read_to_end() {
        $substr = substr($this->input, $this->position);
        return $substr;
    }

    /**
     * Advance the current position of n characters. Meaning they have been successfully parsed.
     * @param  integer $i
     * @return \Khepin\Partibus\Input
     */
    public function advance($i = 1) {
        $this->position += $i;
        return $this;
    }
}