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

        $not_rule_part_parser = c::_and(
            p::_string('!'),
            $rule_part
        );
        $not_rule_part = function(Input $input) use($rule_part, $not_rule_part_parser) {
            $result = $not_rule_part_parser($input);
            array_shift($result);

            return array_merge([t::n('not')], $result);
        };

        $look_rule_part_parser = c::_and(
            p::_string('&'),
            $rule_part
        );
        $look_rule_part = function(Input $input) use ($look_rule_part_parser) {
            $result = $look_rule_part_parser($input);
            array_shift($result);

            return array_merge([t::n('look')], $result);
        };

        $plus_rule_part_parser = c::_and(
            $rule_part,
            p::_string('+')
        );
        $plus_rule_part = function(Input $input) use ($plus_rule_part_parser) {
            $result = $plus_rule_part_parser($input);
            array_pop($result);

            return [t::n('plus'), $result[0]];
        };

        $star_rule_part_parser = c::_and(
            $rule_part,
            p::_string('*')
        );
        $star_rule_part = function(Input $input) use ($star_rule_part_parser) {
            $result = $star_rule_part_parser($input);
            array_pop($result);

            return [t::n('star'), $result[0]];
        };

        $qmark_rule_part_parser = c::_and(
            $rule_part,
            p::_string('?')
        );
        $qmark_rule_part = function(Input $input) use ($qmark_rule_part_parser) {
            $result = $qmark_rule_part_parser($input);
            array_pop($result);

            return [t::n('maybe'), $result[0]];
        };

        $rule_component = c::_or(
            $star_rule_part,
            $plus_rule_part,
            $qmark_rule_part,
            $not_rule_part,
            $look_rule_part,
            $rule_part
        );

        $or_parser = c::_and($ows, Parsers::_string('|'), $ows);
        $or = function(Input $input) use ($or_parser) {
            $or_parser($input);
            return t::n('or');
        };
        $and = function(){return t::n('and');};

        $more = c::maybe(
            c::_and(
                c::_or($or, $and),
                $ows,
                c::_or(c::ref($rule), c::ref($group), c::ref($hidden))
            )
        );
        $rule = c::_and(
            $ows,
            $rule_component,
            $ows,
            $more
        );

        $group_parser = c::_and(
            $ows,
            c::hide(p::_string('(')),
            c::_or(c::ref($rule), c::ref($group), c::ref($hidden)),
            c::hide(p::_string(')'))
        );
        $group = function(Input $input) use ($group_parser, $more) {
            $ret = array_merge([t::n('group')], $group_parser($input));
            $next = $more($input);
            if (!is_null($next)) {
                $ret = [t::n('compose'), $ret, $next];
            }
            return $ret;
        };

        $hidden_parser = c::_and(
            c::hide(p::_string('<')),
            $rule,
            c::hide(p::_string('>'))
        );
        $hidden = function(Input $input) use ($hidden_parser, $more) {
            $ret = array_merge([t::n('hide')], $hidden_parser($input));
            $next = $more($input);
            if (!is_null($next)) {
                $ret = [t::n('compose'), $ret, $next];
            }
            return $ret;
        };

        $full_rule_parser = c::plus(
            c::_and(
                c::_or(
                    $group,
                    $hidden,
                    $rule
                ),
                $more
            )
        );
        $full_rule = function(Input $input) use ($full_rule_parser) {
            return array_merge([t::n('rule')], $full_rule_parser($input)[0]);
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
}