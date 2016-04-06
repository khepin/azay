<?php
require_once __DIR__.'/../vendor/autoload.php';

$parse = \Khepin\Partibus\Parsers::from_grammar(file_get_contents(__DIR__.'/json.bnf'));

$input = '[0.987, 8, 9]';

var_dump($parse($input));