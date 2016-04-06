# Partibus

*What if context-free grammars were as easy to use as regular expressions?*

This library is heavily inspired from [Instaparse](https://github.com/Engelberg/instaparse). Seriously, even this readme is inspired by theirs!

![build status on codeship](https://codeship.com/projects/40ae6ea0-cef1-0133-68e5-5ed74b30bb55/status?branch=master)
![build status on circleci](https://circleci.com/gh/khepin/partibus.svg?style=shield)

## Features

This aims to be the easiest, most simple way to build parsers in PHP.

- Turns EBNF or ABNF like notation for context-free grammars into an executable parser that takes a string as an input and produces a parse tree for that string.
- Extends the power of context-free grammars with PEG-like syntax for lookahead and negative lookahead
- Optional combinator library for building grammars programmatically.

### Mehs

- No support for left recursive or ambigous grammars
- Shitty parse errors (let's fix that!)
- Probably a lot of bugs right now

## Creating a simple parser

```
S = AB*
AB = A B
A = 'a'+
B = 'b'+
```

```php
<?php
$g = 'S = AB*
AB = A B
A = "a"+
B = "b"+';

$parse_as_and_bs = \Khepin\Partibus\Parsers::from_grammar($g);

$parse_as_and_bs('aabb');
```

Will produce an output of:

```
[
    [t::n('S'),
        [t::n('AB'),
            [t::n('A'), 'a', 'a'],
            [t::n('B'), 'b', 'b']
        ]
    ]
]
```

## What is `t`

