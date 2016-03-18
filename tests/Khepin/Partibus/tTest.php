<?php
namespace Khepin\Partibus;

class tTest extends \PHPUnit_Framework_TestCase {

    function test_equality() {
        $this->assertEquals(t::n('bob'), t::n('bob'));
        $this->assertNotEquals(t::n('bob'), t::n('bib'));
    }

    function test___toString() {
        $this->assertEquals('#(t::bob)', (string) t::n('bob'));
    }

    function test_name() {
        $this->assertEquals('bibi', t::n('bibi')->name());
    }
}