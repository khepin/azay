<?php
declare(strict_types=1);
namespace Khepin\Azay;

use Khepin\Azay\Combinators as c;
use Khepin\Azay\Parsers as p;
use Khepin\Azay\BnfGrammar as g;
use Khepin\Azay\t;
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
            // We compile rule names here, otherwise they could have a name like t::n('string') which we would try to compile
            // right here and get a bug.
            if (isset($parser[0]) && isset($parser[0][0]) && $parser[0][0] === t::n('rule_name')) {
                $parser[0] = t::n($parser[0][1]);
            }
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
        self::$transforms[t::n('maybe')] = [c::class, 'maybe'];
        self::$transforms[t::n('star')] = [c::class, 'star'];
        self::$transforms[t::n('plus')] = [c::class, 'plus'];
        self::$transforms[t::n('or')] = [c::class, '_or'];
        self::$transforms[t::n('and')] = function() { // Unsplice the parts that are non tokenized arrays.
            $parser = call_user_func_array([c::class, '_and'], func_get_args());
            return function(Input $input) use ($parser) {
                $output = $parser($input);
                if (is_array($output)) {
                    return self::unsplice($output);
                }
                return $output;
            };
        };
        self::$transforms[t::n('rule')] = function($parser) { return $parser;};
        self::$transforms[t::n('look')] = [c::class, 'look'];
        self::$transforms[t::n('not')] = [c::class, 'not'];
        self::$transforms[t::n('group')] = function($parser) { return $parser;};

        self::$initialized = true;
    }

    static function unsplice(array $array) : array {
        $ret = [];
        foreach($array as $item) {
            if (is_array($item) && !($item[0] instanceof t)) {
                $ret = array_merge($ret, $item);
            } else {
                $ret[] = $item;
            }
        }

        return $ret;
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