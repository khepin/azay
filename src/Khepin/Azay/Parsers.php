<?php
declare(strict_types=1);
namespace Khepin\Azay;

/**
 * Functions for the creation of base parsers.
 */
class Parsers {
    /**
     * Parses a given string exactly and returns it.
     * @param  string $string
     * @return function
     */
    static function _string(string $string) : callable {
        $len = strlen($string);
        return function(Input $input) use ($len, $string) {
            $read = $input->read($len);
            if ($read === $string) {
                $input->advance($len);
                return $string;
            } else {
                throw new ParseException($input);
            }
        };
    }

    /**
     * Parses a string based on a regexp. The match must happen at the beginning of the string.
     * The longest possible match is returned
     * @param  string $regexp
     * @return function
     */
    static function regexp(string $regexp) : callable {
        if ($regexp[0] !== '^') {
            $regexp = '^' . $regexp;
        }
        $regexp = sprintf('/%s/', str_replace('/', '\/', $regexp));
        return function(Input $input) use ($regexp) {
            $matches = [];
            preg_match($regexp, $input->read_to_end(), $matches);
            if (empty($matches)) {
                throw new ParseException($input);
            }

            $match = $matches[0];
            $len = strlen($match);
            $input->advance($len);
            return $match;
        };
    }

    /**
     * Parses the empty string
     * @param  Input  $input
     * @return array
     */
    static function epsilon(Input $input) : array {
        if ($input->read() !== '') {
            throw new ParseException($input);
        }
        return [t::n('ε')];
    }

    /**
     * Returns the epsilon parser as a closure
     * @return callable
     */
    static function get_epsilon() : callable {
        return function(Input $input) {
            return self::epsilon($input);
        };
    }

    /**
     * This should be the main entry point of this library.
     * It will create a full parser based on the EBNF grammar string.
     *
     * The returned parser consumes a string as its input. No need to pass it an Input object.
     * @param  string $grammar_string
     * @return array
     */
    static function from_grammar(string $grammar_string, array $options = []) : callable {
        $parser = Compiler::compile(BnfGrammar::parse($grammar_string));

        if (!isset($options['partial']) || $options['partial'] === true) {
            $parser = Combinators::_and($parser, Combinators::hide(self::get_epsilon()));
        }
        if (isset($options['raw_parser']) && $options['raw_parser'] === true) {
            return $parser;
        }
        return function($string_input) use ($parser) {
            return $parser(new Input($string_input));
        };
    }
}