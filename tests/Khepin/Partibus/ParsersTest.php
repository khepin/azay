<?php
namespace Khepin\Azay;

class ParsersTest extends \PHPUnit_Framework_TestCase {

    function test_epsilon() {
        $this->assertEquals([t::n('ε')], Parsers::epsilon(new Input('')));

        $this->expectException(\Exception::class);
        Parsers::epsilon(new Input('hello'));
    }

    function test_get_epsilon() {
        $parser = Parsers::get_epsilon();
        $this->assertEquals([t::n('ε')], $parser(new Input('')));
    }

    function test__string() {
        $parser = Parsers::_string('bibi');
        $input = new Input('hello bibi');
        $input->advance(6);
        $this->assertEquals('bibi', $parser($input));

        $parser = Parsers::_string('');
        $this->assertEquals('', $parser(new Input('')));
        $this->assertEquals('', $parser(new Input('abc')));
        $this->assertEquals('', $parser(new Input('abc')));

        $parser = Parsers::_string('bibi');
        $this->assertEquals('bibi', $parser(new Input('bibi')));
        $this->assertEquals('bibi', $parser(new Input('bibibaba')));
        $this->expectException(\Exception::class);
        $parser(new Input('hello'));
    }

    function test_regexp() {
        $parser = Parsers::regexp('a');
        $this->assertEquals('a', $parser(new Input('abc')));

        $parser = Parsers::regexp('a+');
        $this->assertEquals('a', $parser(new Input('abc')));
        $this->assertEquals('aaa', $parser(new Input('aaabc')));
    }

    /**
     * @dataProvider regexp_failures
     * @param  string $regexp
     * @param  string $input
     */
    function test_regexp_failures($regexp, $input) {
        $this->expectException(\Exception::class);
        $parser = Parsers::regexp($regexp);
        $parser(new Input($input));
    }

    function regexp_failures() {
        return [
            ['a+', 'eeaaaaa'],
            ['a*eeb', 'eea']
        ];
    }

    /**
     * @dataProvider regexp_successes
     * @param  string $regexp
     * @param  string $input
     * @param  string $parsed
     */
    function test_regexp_successes($regexp, $input, $parsed) {
        $parser = Parsers::regexp($regexp);
        $this->assertEquals($parsed, $parser(new Input($input)));
    }

    function regexp_successes() {
        return [
            ['a+', 'aaaa', 'aaaa'],
            ['[^\d]+', 'abcdef', 'abcdef'],
            ['[^\d]+', 'abcdef876', 'abcdef'],
            ['^..$', 'ab', 'ab'],
        ];
    }
}