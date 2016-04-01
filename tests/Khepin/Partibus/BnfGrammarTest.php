<?php
namespace Khepin\Partibus;

use Khepin\Partibus\Combinators as c;
use Khepin\Partibus\Parsers as p;

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
                    [[t::n('rule_name'), "def"], [t::n('rule'), [t::n('not'), [t::n('string'), 'a']], [t::n('and'), [t::n('string'), 'boom']]]]
                ]
            ],
            [
                'def = &"a" "boom"',
                [
                    [[t::n('rule_name'), "def"], [t::n('rule'), [t::n('look'), [t::n('string'), 'a']], [t::n('and'), [t::n('string'), 'boom']]]]
                ]
            ],
            [
                'def = "a"+ "boom"',
                [
                    [[t::n('rule_name'), "def"], [t::n('rule'), [t::n('plus'), [t::n('string'), 'a']], [t::n('and'), [t::n('string'), 'boom']]]]
                ]
            ],
            [
                'def = "a"* "boom"',
                [
                    [[t::n('rule_name'), "def"], [t::n('rule'), [t::n('star'), [t::n('string'), 'a']], [t::n('and'), [t::n('string'), 'boom']]]]
                ]
            ],
            [
                'def = "a"? "boom"',
                [
                    [[t::n('rule_name'), "def"], [t::n('rule'), [t::n('maybe'), [t::n('string'), 'a']], [t::n('and'), [t::n('string'), 'boom']]]]
                ]
            ],
            [
                'def = <"a"> "boom"',
                [
                    [[t::n('rule_name'), "def"], [t::n('rule'), [t::n('compose'), [t::n('hide'), [t::n('string'), 'a']], [t::n('and'), [t::n('string'), 'boom']]]]]
                ]
            ],
            [
                'def = <"a" "boom">',
                [
                    [[t::n('rule_name'), "def"], [t::n('rule'), [t::n('hide'), [t::n('string'), 'a'], [t::n('and'), [t::n('string'), 'boom']]]]]
                ]
            ],
            [

                'def = "a" bloup | "b"
bloup = #"a+"',
                [
                    [
                        [t::n('rule_name'),"def"],
                        [
                            t::n('rule'),
                            [
                                t::n('string'),
                                "a"
                            ],[
                                t::n('and'),
                                [
                                    t::n('ref'),
                                    "bloup"
                                ],
                                [
                                    t::n('or'),
                                    [
                                        t::n('string'),
                                        "b"
                                    ]
                                ]
                            ]
                        ]
                    ],
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
            // [
            //     'def = a b | c d',
            //     [[t::n('rule_name'),'def'], [t::n('rule'),[t::n('maybe'),[t::n('group'),[t::n('string'),'group']]]]]
            // ],
        ];
    }
}