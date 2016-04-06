<?php
declare(strict_types=1);
namespace Khepin\Partibus;

use Khepin\Partibus\Combinators as c;
use Khepin\Partibus\Parsers as p;

/**
 * A somewhat similar to BNF grammar. This can be used to parse parser definitions.
 */
class BnfGrammar {
    /**
     * Parses a parser definition in Bnf form
     * @param  string $input
     * @return array
     */
    static function parse(string $input) : array {
        // We'll parse single lines rather than a whole file
        $lines = explode("\n", $input);
        $lines = array_map(function($line){ return trim($line); }, $lines);
        // Remove commented lines
        $lines = array_filter($lines, function(string $line) : bool {return !(substr($line, 0, 2) === '//');});

        return array_map(function(string $line) : array {
            return self::_parse(new Input($line));
        }, $lines);
    }

    /**
     * Parses a parser definition in Bnf form.
     * @param  Input  $input
     * @return array
     */
    static function _parse(Input $input) : array {
        $token_name = '([a-zA-Z-_]+|ε)';
        $token_parser = p::regexp($token_name);
        $token = function(Input $input) use ($token_parser) {
            return [t::n('ref'), $token_parser($input)];
        };
        $rule_name_regexp = sprintf('%s|<%s>', $token_name, $token_name);
        $rule_name_parser = p::regexp($rule_name_regexp);
        $rule_name = function(Input $input) use ($rule_name_parser) {
            return [t::n('rule_name'), $rule_name_parser($input)];
        };

        $ows = c::hide(p::regexp('\s*'));

        $assign_parser = p::regexp('(=|:=|::|:)');
        $assign = function(Input $input) use ($assign_parser) {
            return [t::n('assign'), $assign_parser($input)];
        };

        $string_parser = c::_or(
            p::regexp('"(\\\"|[^"])*"'),
            p::regexp("'(\\\'|[^'])*'")
        );
        $string = function(Input $input) use ($string_parser) {
            $result = $string_parser($input);
            return [t::n('string'), substr($result, 1, strlen($result) - 2)];
        };

        $regex_parser = c::_or(
            p::regexp('#"(\\\"|[^"])*"'),
            p::regexp("#'(\\\'|[^'])*'")
        );
        $regexp = function(Input $input) use ($regex_parser) {
            $result = $regex_parser($input);
            return [t::n('regexp'), substr($result, 2, strlen($result) - 3)];
        };

        $rule_part = c::_and(c::_or(
            $regexp,
            $string,
            $token
        ));

        $rule_component = self::with_modifiers($rule_part);

        $rule_parser = c::_and(
            $ows,
            $rule_component,
            $ows
        );
        $rule = function(Input $input) use ($rule_parser) {
            return $rule_parser($input)[0];
        };

        $group_parser = c::_and(
            $ows,
            c::hide(p::_string('(')),
            c::_or(c::ref($alternate_rule), c::ref($group), c::ref($hidden)),
            c::hide(p::_string(')'))
        );
        $group = function(Input $input) use ($group_parser) {
            $ret = array_merge([t::n('group')], $group_parser($input));
            return $ret;
        };
        $group = self::with_modifiers($group);

        $hidden_parser = c::_and(
            $ows,
            c::hide(p::_string('<')),
            c::ref($alternate_rule),
            c::hide(p::_string('>'))
        );
        $hidden = function(Input $input) use ($hidden_parser) {
            $ret = array_merge([t::n('hide')], $hidden_parser($input));
            return $ret;
        };

        $concat_rule_parser = c::plus(
                c::_or(
                    $hidden,
                    $group,
                    $rule
                )
        );
        $concat_rule = function(Input $input) use ($concat_rule_parser) {
            $parsed = $concat_rule_parser($input);
            if (count($parsed) > 1) {
                return array_merge([t::n('and')], $parsed);
            }
            return $parsed[0];
        };

        $alternate_rule_parser = c::_and(
            $concat_rule,
            $ows,
            c::star(c::_and(c::hide(p::_string('|')), $concat_rule, $ows))
        );
        $alternate_rule = function(Input $input) use ($alternate_rule_parser) {
            $parsed = $alternate_rule_parser($input);
            if (count($parsed) > 1) {
                return array_merge([t::n('or'), array_shift($parsed)], array_map('array_shift', $parsed));
            }
            return $parsed[0];
        };

        $full_rule = function(Input $input) use ($alternate_rule) {
            return [t::n('rule'), $alternate_rule($input)];
        };

        $parse = c::_and(
            $rule_name,
            $ows,
            c::hide($assign),
            $ows,
            $full_rule,
            $ows,
            c::hide(p::get_epsilon())
        );

        return $parse($input);
    }

    /**
     * Returns a parser that can parse the given parser with all the `+, *, !, &, ?`
     * modifiers added to it.
     * @param  callable $base_parser
     * @return callable
     */
    public static function with_modifiers(callable $base_parser) : callable {
        $not_parser = c::_and(
            p::_string('!'),
            $base_parser
        );
        $not = function(Input $input) use($base_parser, $not_parser) {
            $result = $not_parser($input);
            array_shift($result);

            return array_merge([t::n('not')], $result);
        };

        $look_parser = c::_and(
            p::_string('&'),
            $base_parser
        );
        $look = function(Input $input) use ($look_parser) {
            $result = $look_parser($input);
            array_shift($result);

            return array_merge([t::n('look')], $result);
        };

        $plus_parser = c::_and(
            $base_parser,
            p::_string('+')
        );
        $plus = function(Input $input) use ($plus_parser) {
            $result = $plus_parser($input);
            array_pop($result);

            return [t::n('plus'), $result[0]];
        };

        $star_parser = c::_and(
            $base_parser,
            p::_string('*')
        );
        $star = function(Input $input) use ($star_parser) {
            $result = $star_parser($input);
            array_pop($result);

            return [t::n('star'), $result[0]];
        };

        $qmark_parser = c::_and(
            $base_parser,
            p::_string('?')
        );
        $qmark = function(Input $input) use ($qmark_parser) {
            $result = $qmark_parser($input);
            array_pop($result);

            return [t::n('maybe'), $result[0]];
        };

        return c::_or(
            $star,
            $plus,
            $qmark,
            $not,
            $look,
            $base_parser
        );
    }
}