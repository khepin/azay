<?php
require_once __DIR__.'/../vendor/autoload.php';

$parse = \Khepin\Partibus\Parsers::from_grammar(file_get_contents(__DIR__.'/xml.bnf'));

$input = '<TagName name="bob" nickname="0)_"></TagName>';
$input = '<TagName >
    <Mike>hi I am mike<Bob/></Mike>
</TagName>';

var_dump($parse($input));