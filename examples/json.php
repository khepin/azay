<?php
require_once __DIR__.'/../vendor/autoload.php';

function stringify($tree) {
    array_walk_recursive($tree, function(&$item) {$item = (string) $item;});
    return $tree;
}

$parse = \Khepin\Partibus\Parsers::from_grammar(file_get_contents(__DIR__.'/json.bnf'));

$input = '[1,2,3, [4,5,6]]';
// $input = '{ "h": "bob", "i":"bloup" }';
$output = $parse($input);
var_dump(stringify($output));