<?php
namespace Khepin\Azay;

class tTest extends \PHPUnit_Framework_TestCase {

    function test_equality() {
        $this->assertEquals(t::n('bob'), t::n('bob'));
        $this->assertNotEquals(t::n('bob'), t::n('bib'));
    }

    function test___toString() {
        $this->assertEquals('t::n(bob)', (string) t::n('bob'));
    }

    function test_name() {
        $this->assertEquals('bibi', t::n('bibi')->name());
    }

    function test_invoke() {
        $map = new \splObjectStorage;
        $map[t::n('bob')] = 'hello';

        $this->assertEquals('hello', t::n('bob')($map));

        $map = ['bob' => 'haha'];
        $this->assertEquals('haha', t::n('bob')($map));
    }
}