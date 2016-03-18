<?php
namespace Khepin\Partibus;

class InputTest extends \PHPUnit_Framework_TestCase {
    function test_reading_input() {
        $input_text = 'Hello my name is bob';
        $input = new Input($input_text);

        $this->assertEquals($input_text, $input->read_to_end());

        $input->advance(5);
        $this->assertEquals(' my name is bob', $input->read_to_end());
        $this->assertEquals(' ', $input->read());
        $this->assertEquals(' my n', $input->read(5));
        $this->assertEquals(' my name is bob', $input->read(20000));
    }
}