<?php
declare(strict_types=1);
namespace Khepin\Partibus;

/**
 * Functions to combine or augment existing parse functions
 */
class Combinators {

    /**
     * Combines any number of parsers so that the resulting parser passes
     * only if all the supplied parsers passed in the order they were given.
     * @return callable
     */
    static function _and() : callable {
        $parsers = func_get_args();

        return function(Input $input) use ($parsers) {
            $outputs = [];
            foreach($parsers as $parser) {
                $result = $parser($input); // Let them fail and send exceptions if needed.
                if (is_array($result) && !empty($result) && !($result[0] instanceof t)) {
                    $outputs = array_merge($outputs, $result);
                } else {
                    if (!is_null($result) && !empty($result)) {
                        $outputs[] = $result;
                    }
                }
            }

            return $outputs;
        };
    }

    /**
     * Combines any number of parsers so that the resulting parser will try them
     * successively until one of them passes. The result of that parser will be sent back.
     * Only the input consumed by that parser will be consumed.
     * @return callable
     */
    static function _or() : callable {
        $parsers = func_get_args();

        return static function (Input $input) use ($parsers) {
            $pos = $input->position;
            foreach($parsers as $parser) {
                try {
                    return $parser($input);
                } catch (ParseException $e) {
                    // Reset the input if it had advanced
                    $input->position = $pos;
                }
            }

            // If we get here, none of the parsers have succeeded. Throw error.
            throw new ParseException($input);
        };
    }

    /**
     * Takes a single parser and returns a parser that will match the given parser
     * 0 or more times.
     * @param  callable $parser
     * @return callable
     */
    static function star(callable $parser) : callable {
        return function(Input $input) use ($parser){
            $output = [];
            $pos = 0;
            try {
                while(true) {
                    $pos = $input->position;
                    $output[] = $parser($input);
                }
            } catch (ParseException $e) {
                $input->position = $pos;
            }
            if (empty($output)) {
                return null;
            }

            return $output;
        };
    }

    /**
     * Takes a parser A and returns a parser that will succeed if A can parse the input,
     * in which case A's output will be returned.
     * If A fails, this will also succeed and produce a null output
     * @param  callable $parser
     * @return callable
     */
    static function maybe(callable $parser) : callable {
        return function(Input $input) use ($parser){
            $pos = $input->position;
            try {
                return  $parser($input);
            } catch (ParseException $e) {
                $input->position = $pos;
            }
        };
    }

    /**
     * Same as ::star except it's one or more times instead of 0 or more
     * @see    Khepin\Partibus\Combinators::star
     * @param  callable $parser
     * @return callable
     */
    static function plus(callable $parser) : callable {
        return function(Input $input) use ($parser) {
            $output = [];
            $output[] = $parser($input); // It must be there at least once so if this gives an exception, let it fail.

            $parse_rest = self::star($parser);
            $rest = $parse_rest($input);
            if (is_null($rest)) {
                $rest = [];
            }

            return array_merge($output, $rest);
        };
    }

    /**
     * Takes a parser A and returns a parser that succeeds if A succeeds but does not consume
     * any input.
     * @param  callable $parser
     * @return callable
     */
    static function look(callable $parser) : callable {
        return function(Input $input) use ($parser) {
            $pos = $input->position;
            $parser($input);
            $input->position = $pos;
            return null;
        };
    }

    /**
     * Takes a parser A and returns a parser that succeeds if A fails and does not consume
     * any input
     * @param  callable $parser
     * @return callable
     */
    static function not(callable $parser) : callable {
        return function(Input $input) use ($parser){
            $try_parser = self::look($parser);
            try {
                $try_parser($input);
            } catch (ParseException $e) {
                return null;
            }
            throw new ParseException($input);
        };
    }

    /**
     * Takes a yet undefined parser A and returns a parser that will parse when A succeeds.
     * A does not have to be defined at the time of this call. It is sufficient to hold a reference
     * to where it will be defined.
     * This allows the creation of recursive parsers.
     * @param  callable &$parser
     * @return callable
     */
    static function ref(&$parser) : callable {
        return function(Input $input) use (&$parser) {
            return $parser($input);
        };
    }

    /**
     * Similar to ::ref but uses a reference within a given grammar where a grammar is an splObjectStorage
     * map of t -> parser
     * @see    Khepin\Partibus\Combinators::ref
     * @param  \splObjectStorage &$grammar
     * @param  t                 $ref
     * @return callable
     */
    static function grammar_ref(\splObjectStorage &$grammar, t $ref) : callable {
        return function(Input $input) use (&$grammar, $ref) {
            return $grammar[$ref]($input);
        };
    }

    /**
     * Takes a parser A and returns a parser that succeeds when A succeeds but always produces a null output.
     * Useful when the output isn't important such as parsing white spaces for example.
     * @param  callable $parser
     * @return callable
     */
    static function hide(callable $parser) : callable {
        return function(Input $input) use ($parser) {
            $parser($input);
            return null;
        };
    }
}