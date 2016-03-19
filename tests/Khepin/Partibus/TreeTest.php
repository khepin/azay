<?php
namespace Khepin\Partibus;

class TreeTest extends \PHPUnit_Framework_TestCase {

    static $tree = [];

    static function setupBeforeClass() {
        self::$tree = [t::n('root'), [t::n('string'), 'hello'], [t::n('int'), '4']];
    }

    function test_rest() {
        $a = [];
        $this->assertEquals([], Tree::rest($a));

        $a = range(1, 10);
        $this->assertEquals(range(2, 10), Tree::rest($a));
    }

    function test_traverse() {
        $tree = self::$tree;
        $traversed = Tree::traverse($tree, function($item, $values) {
            return array_merge([(string) $item], $values);
        });

        $this->assertEquals($traversed, ['t::n(root)', ['t::n(string)', 'hello'], ['t::n(int)', '4']]);
    }

    function test_transform() {
        $tree = self::$tree;
        $transforms = new \splObjectStorage;
        $transforms[t::n('int')] = function($int) {return (int) $int;};

        $transformed = Tree::transform($tree, $transforms);

        $this->assertEquals($transformed, [t::n('root'), [t::n('string'), 'hello'], 4]);

        $transforms[t::n('string')] = function($s) {return $s;};
        $transformed = Tree::transform($tree, $transforms);
        $this->assertEquals($transformed, [t::n('root'), 'hello', 4]);

        $transforms[t::n('root')] = function($string, $int) {return str_repeat($string, $int);};
        $transformed = Tree::transform($tree, $transforms);
        $this->assertEquals($transformed, 'hellohellohellohello');
    }
}