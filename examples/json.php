<?php
require_once __DIR__.'/../vendor/autoload.php';

function stringify($tree) {
    array_walk_recursive($tree, function(&$item) {$item = (string) $item;});
    return $tree;
}

$parse = \Khepin\Azay\Parsers::from_grammar(file_get_contents(__DIR__.'/json.bnf'));

$input = '[1,2,3, [4,5,6]]';
$input = '{ "h": "bob", "i":"bloup" }';
$output = $parse($input);
var_dump(stringify($output));

// Compiling
use Khepin\Azay\t;

function identity($thing) {
    return $thing;
}

$transforms = new \splObjectStorage;
$transforms[t::n('number')] = 'identity';
$transforms[t::n('string')] = 'identity';
$transforms[t::n('bool')] = 'identity';
$transforms[t::n('array')] = function() {
    return func_get_args();
};
$transforms[t::n('key')] = 'identity';
$transforms[t::n('value')] = 'identity';
$transforms[t::n('pair')] = function($key, $value) {
    return [$key, $value];
};
$transforms[t::n('object')] = function() {
    $items = func_get_args();
    $obj = [];
    foreach($items as $item) {
        $obj[$item[0]] = $item[1];
    }
    return (object) $obj;
};

var_dump(\Khepin\Azay\Tree::transform($output, $transforms));