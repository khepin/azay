<?php
declare(strict_types=1);
namespace Khepin\Partibus;

use Khepin\Partibus\Combinators as c;
use Khepin\Partibus\Parsers as p;
use Khepin\Partibus\BnfGrammar as g;
use Khepin\Partibus\t;
use \splObjectStorage;

class Compiler {

    static $initialized = false;

    static $transforms = null;

    static function compile(array $asts) : callable {
        self::initialize();
        $grammar = new splObjectStorage;
        self::$transforms[t::n('ref')] = function(string $ref_name) use (&$grammar) : callable {
            if ($ref_name === 'ε') {
                return p::get_epsilon();
            }
            return c::grammar_ref($grammar, t::n($ref_name));
        };

        array_map(function(array $ast) use(&$grammar) {
            $parser = Tree::transform($ast, self::$transforms);
            $parser = self::add_grammar_parser($grammar, $parser);

            if (!isset($grammar->start)) {
                $grammar->start = $parser;
            }
        }, $asts);

        return $grammar->start;
    }

    static function initialize() {
        if (self::$initialized) {
            return;
        }
        self::$transforms = new splObjectStorage;
        self::$transforms[t::n('string')] = function($string) { return p::_string($string);};
        self::$transforms[t::n('regexp')] = function($string) { return p::regexp($string);};
        self::$transforms[t::n('hide')] = function($parser) {
            return c::hide($parser);
        };
        self::$transforms[t::n('maybe')] = function($parser) {
            return c::maybe($parser);
        };
        self::$transforms[t::n('star')] = function($parser) {
            return c::star($parser);
        };
        self::$transforms[t::n('plus')] = function($parser) {
            return c::plus($parser);
        };
        self::$transforms[t::n('or')] = function($parser, array $rest = []) {
            return [t::n('or'), self::compile_and_or($parser, $rest)];
        };
        self::$transforms[t::n('and')] = function($parser, array $rest = []) {
            return [t::n('and'), self::compile_and_or($parser, $rest)];
        };
        self::$transforms[t::n('rule')] = function($parser, array $rest = []) {
            return self::compile_and_or($parser, $rest);
        };
        self::$transforms[t::n('rule_name')] = function($name) {return t::n($name);};
        self::$transforms[t::n('look')] = function($parser) {
            return c::look($parser);
        };
        self::$transforms[t::n('not')] = function($parser) {
            return c::not($parser);
        };
        self::$transforms[t::n('group')] = function($parser, array $rest = []) {
            $actual_parser = self::compile_and_or($parser, $rest);
            return function(Input $input) use ($actual_parser) {
                $ret = $actual_parser($input);
                if (is_null($ret)) {
                    return null;
                }
                return [$ret];
            };
        };
        self::$transforms[t::n('compose')] = function($parser, array $rest = []) {
            return self::compile_and_or($parser, $rest);
        };

        self::$initialized = true;
    }

    static function compile_and_or(callable $parser, array $rest) : callable {
        if (empty($rest)) {
            return $parser;
        }

        if ($rest[0] === t::n('and')) {
            return c::_and($parser, $rest[1]);
        }

        if ($rest[0] === t::n('or')) {
            return c::_or($parser, $rest[1]);
        }
    }

    static function add_grammar_parser(splObjectStorage &$grammar, array $parser) : callable {
        $name = $parser[0];
        if (preg_match('/^<([^>]+)>$/', $parser[0]->name(), $matches)) {
            $name = t::n($matches[1]);
        }

        $grammar[$name] = function(Input $input) use ($parser) {
            $ret = $parser[1]($input);
            if (
                is_array($ret)
                && !empty($ret)
                && preg_match('/^<[^>]+>$/', $parser[0]->name())
            ) {// We unsplice tokens that have names surrounded by < ... >)
                return $ret;
            }
            if (empty($ret)) {
                return null;
            }
            if (is_array($ret) && !($ret[0] instanceof t)) {
                return array_merge([$parser[0]], $ret);
            }
            return [$parser[0], $ret];
        };

        return $grammar[$name];
    }

}