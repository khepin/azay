<?php
namespace Khepin\Partibus;

use Khepin\Partibus\Combinators as c;
use Khepin\Partibus\Parsers as p;

class CombinatorsTest extends \PHPUnit_Framework_TestCase {
    function test__and() {
        $parser = c::_and(p::_string('a'));
        $this->assertEquals(['a'], $parser(new Input('a')));

        $parser = c::_and(p::_string('a'), p::_string('b'));
        $this->assertEquals(['a', 'b'], $parser(new Input('ab')));

        $this->expectException(ParseException::class);
        $parser = c::_and(p::_string('a'), p::_string('a'));
        $parser(new Input('ab'));
    }

    function test__or() {
        $parser = c::_or(p::_string('a'));
        $this->assertEquals('a', $parser(new Input('a')));

        $parser = c::_or(p::_string('a'), p::_string('b'));
        $this->assertEquals('a', $parser(new Input('a')));
        $this->assertEquals('b', $parser(new Input('b')));

        $this->expectException(ParseException::class);
        $parser(new Input('c'));
    }

    function test_star() {
        $parser = c::star(p::_string('a'));

        $this->assertEquals('', $parser(new Input('')));
        $this->assertEquals(['a'], $parser(new Input('a')));
        $this->assertEquals(['a', 'a'], $parser(new Input('aa')));
        $this->assertEquals(['a', 'a', 'a'], $parser(new Input('aaa')));
    }

    function test_maybe() {
        $parser = c::maybe(p::_string('a'));

        $this->assertEquals('a', $parser(new Input('a')));
        $this->assertEquals('', $parser(new Input('')));
        $this->assertEquals('', $parser(new Input('b')));
    }

    function test_plus() {
        $parser = c::plus(p::_string('a'));

        $this->assertEquals(['a'], $parser(new Input('a')));
        $this->assertEquals(['a', 'a'], $parser(new Input('aa')));
        $this->assertEquals(['a', 'a', 'a'], $parser(new Input('aaa')));

        $this->expectException(ParseException::class);
        $parser(new Input(''));
    }

    function test_look() {
        $parser = c::look(p::_string('a'));

        $input = new Input('a');
        $this->assertNull($parser($input));
        $this->assertEquals(0, $input->position);

        $this->expectException(ParseException::class);
        $parser(new Input('b'));
    }

    function test_not() {
        $parser = c::not(p::_string('a'));

        $input = new Input('b');
        $this->assertNull($parser($input));
        $this->assertEquals(0, $input->position);

        $this->expectException(ParseException::class);
        $parser(new Input('a'));
    }

    function test_ref() {
        $parser = c::ref($p);
        $p = p::_string('a');

        $this->assertEquals('a', $parser(new Input('a')));

        // Recursivity
        $parser = c::_and(
            p::_string('a'),
            c::maybe(c::ref($parser))
        );
        $this->assertEquals(['a'], $parser(new Input('a')));
        $this->assertEquals(['a', 'a'], $parser(new Input('aa')));
    }

    function test_grammar_ref() {
        $grammar = new \splObjectStorage;
        $parser = c::grammar_ref($grammar, t::n('str_a'));

        $grammar[t::n('str_a')] = p::_string('a');

        $this->assertEquals('a', $parser(new Input('a')));
    }

    function test_hide() {
        $parser = c::hide(p::_string('a'));
        $this->assertNull($parser(new Input('a')));
    }
}