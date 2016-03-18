<?php
declare(strict_types=1);
namespace Khepin\Partibus;

use \splObjectStorage;

/**
 * Useful function for AST manipulation
 */
class Tree {

    /**
     * Returns the array minus the first element
     * @param  array  $a
     * @return array
     */
    static function rest(array $a) : array {
        array_shift($a);
        return $a;
    }

    /**
     * Applies a callback function to every array. The callback function takes 2 arguments:
     *  - $node, the first element of the current array
     *  - $values, the rest of the array (everything except the first element)
     * This gives a LISP-like way to evaluate and traverse the array
     * @param  mixed $tree
     * @param  callable $fn
     * @return mixed
     */
    static function traverse($tree, callable $fn) {
        if (!is_array($tree)) {
            return $tree;
        }
        $recur = function($node) use ($fn) {
            return self::traverse($node, $fn);
        };
        $compiled_tree = array_map($recur, $tree);

        $node = $compiled_tree[0];
        $rest = self::rest($compiled_tree);

        return $fn($node, $rest);
    }

    /**
     * Will traverse the array and whenever the first element of an array is a key of the $map,
     * the associated callback will be executed.
     * Given an array:
     *  [t::n('root'), [t::n('string'), 'I am a string'], [t::n('int'), '2']]
     * And a map:
     *  $map = new \splObjectStorage;
     *  $map[t::n('string')] = function($s) {return $s;};
     *  $map[t::n('ing')] = function($i) {return (int) $i;};
     *
     * The resulting tree would be:
     * [t::n('root'), 'I am a string', 2]
     *
     * @param  array $tree
     * @param  splObjectStorage $map
     * @return mixed
     */
    static function transform(array $tree, splObjectStorage $map) {
        $transform_fn = function($item, array $values = []) use ($map) {
            if (is_object($item) && $map->offsetExists($item) && is_callable($map[$item])) {
                return call_user_func_array($map[$item], $values);
            }
            return array_merge([$item], $values);
        };

        return self::traverse($tree, $transform_fn);
    }
}