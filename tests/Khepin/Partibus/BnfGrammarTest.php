<?php
namespace Khepin\Azay;

use Khepin\Azay\Combinators as c;
use Khepin\Azay\Parsers as p;

/**
 * @group bnfgrammar
 */
class BnfGrammarTest extends \PHPUnit_Framework_TestCase {
    /**
     * @dataProvider samples
     */
    function test_parsing($input, $output) {
        // $parsed = BnfGrammar::parse($input);
        // array_walk_recursive($parsed, function(&$item) {$item = (string) $item;});
        // var_dump($parsed);
        $this->assertEquals(BnfGrammar::parse($input), $output);

    }

    function samples() {
        return [
            [
                'def = "a"',
                [
                    [[t::n('rule_name'), "def"], [t::n('rule'), [t::n('string'), 'a']]]
                ]
            ],
            [
                'def = !"a" "boom"',
                [
                    [[t::n('rule_name'), "def"], [t::n('rule'), [t::n('and'),
                                                                    [t::n('not'), [t::n('string'), 'a']],
                                                                    [t::n('string'), 'boom']]]]
                ]
            ],
            [
                'def = &"a" "boom"',
                [
                    [[t::n('rule_name'), "def"], [t::n('rule'), [t::n('and'),
                                                                    [t::n('look'), [t::n('string'), 'a']],
                                                                    [t::n('string'), 'boom']]]]
                ]
            ],
            [
                'def = "a"+ "boom"',
                [
                    [[t::n('rule_name'), "def"], [t::n('rule'), [t::n('and'),
                                                                    [t::n('plus'), [t::n('string'), 'a']],
                                                                    [t::n('string'), 'boom']]]]
                ]
            ],
            [
                'def = "a"* "boom"',
                [
                    [[t::n('rule_name'), "def"], [t::n('rule'), [t::n('and'),
                                                                    [t::n('star'), [t::n('string'), 'a']],
                                                                    [t::n('string'), 'boom']]]]
                ]
            ],
            [
                'def = "a"? "boom"',
                [
                    [[t::n('rule_name'), "def"], [t::n('rule'), [t::n('and'),
                                                                    [t::n('maybe'), [t::n('string'), 'a']],
                                                                    [t::n('string'), 'boom']]]]
                ]
            ],
            [
                'def = <"a"> "boom"',
                [
                    [[t::n('rule_name'), "def"], [t::n('rule'), [t::n('and'),
                                                                    [t::n('hide'), [t::n('string'), 'a']],
                                                                    [t::n('string'), 'boom']]]]
                ]
            ],
            [
                'def = <"a" "boom">',
                [
                    [[t::n('rule_name'), "def"], [t::n('rule'), [t::n('hide'),
                                                                        [t::n('and'),
                                                                            [t::n('string'), 'a'],
                                                                            [t::n('string'), 'boom']]]]]
                ]
            ],
            [

                'def = "a" bloup | "b"
                bloup = #"a+"',
                [
                    [[t::n('rule_name'), 'def'], [t::n('rule'), [t::n('or'),
                                                                    [t::n('and'),
                                                                        [t::n('string'), 'a'],
                                                                        [t::n('ref'), 'bloup']],
                                                                    [t::n('string'), 'b']]]],
                    [[t::n('rule_name'),"bloup"],[t::n('rule'),[t::n('regexp'),"a+"]]]
                ]
            ],
            [
                'def = ("group")?',
                [[[t::n('rule_name'),'def'], [t::n('rule'),[t::n('maybe'),[t::n('group'),[t::n('string'),'group']]]]]]
            ],
            [
                'def = ("group")+',
                [[[t::n('rule_name'),'def'], [t::n('rule'),[t::n('plus'),[t::n('group'),[t::n('string'),'group']]]]]]
            ],
            [
                'def = ("group")*',
                [[[t::n('rule_name'),'def'], [t::n('rule'),[t::n('star'),[t::n('group'),[t::n('string'),'group']]]]]]
            ],
            [
                'def = &("group")',
                [[[t::n('rule_name'),'def'], [t::n('rule'),[t::n('look'),[t::n('group'),[t::n('string'),'group']]]]]]
            ],
            [
                'def = !("group")',
                [[[t::n('rule_name'),'def'], [t::n('rule'),[t::n('not'),[t::n('group'),[t::n('string'),'group']]]]]]
            ],
            [
                'def =  !("group")',
                [[[t::n('rule_name'),'def'], [t::n('rule'),[t::n('not'),[t::n('group'),[t::n('string'),'group']]]]]]
            ],
            [
                'def =  !"b"',
                [[[t::n('rule_name'),'def'], [t::n('rule'),[t::n('not'),[t::n('string'),'b']]]]]
            ],
            [
                'def =  !("group") "a" "b"',
                [[[t::n('rule_name'),'def'], [t::n('rule'),[t::n('and'), [t::n('not'),[t::n('group'),[t::n('string'),'group']]],
                                                                         [t::n('string'), 'a'],
                                                                         [t::n('string'), 'b']]]]]
            ],
            [
                'def =  !("group" "bob") "a"',
                [[[t::n('rule_name'),'def'], [t::n('rule'),[t::n('and'), [t::n('not'),[t::n('group'),
                                                                                            [t::n('and'), [t::n('string'),'group'],
                                                                                                          [t::n('string'), 'bob']]]],
                                                                         [t::n('string'), 'a']]]]]
            ],
            [
                'def =  a f | b e | c d',
                [[[t::n('rule_name'),'def'], [t::n('rule'),[t::n('or'),
                                                                [t::n('and'), [t::n('ref'), 'a'], [t::n('ref'), 'f']],
                                                                [t::n('and'), [t::n('ref'), 'b'], [t::n('ref'), 'e']],
                                                                [t::n('and'), [t::n('ref'), 'c'], [t::n('ref'), 'd']],]]]]
            ],
            [
                'def = b | a (x | y)',
                [[[t::n('rule_name'),'def'], [t::n('rule'),[t::n('or'),
                                                                [t::n('ref'), 'b'],
                                                                [t::n('and'), [t::n('ref'), 'a'],
                                                                              [t::n('group'),
                                                                                    [t::n('or'),
                                                                                        [t::n('ref'), 'x'],
                                                                                        [t::n('ref'), 'y']]]]]]]]
            ],
            [
                'def =  !("group") "a" "b" | "hello"',
                [[[t::n('rule_name'),'def'], [t::n('rule'), [t::n('or'),
                                                                [t::n('and'), [t::n('not'), [t::n('group'), [t::n('string'), 'group']]],
                                                                              [t::n('string'), 'a'],
                                                                              [t::n('string'), 'b']],
                                                                [t::n('string'), 'hello']]]]]
            ],
            [
                "tag = <'a'> | <'b'>",
                [[[t::n('rule_name'), 'tag'], [t::n('rule'), [t::n('or'),
                                                                [t::n('hide'), [t::n('string'), 'a']],
                                                                [t::n('hide'), [t::n('string'), 'b']]]]]]
            ]
        ];
    }
}