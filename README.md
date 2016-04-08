# Azay

*What if context-free grammars were as easy to use as regular expressions?*

This library is heavily inspired from [Instaparse](https://github.com/Engelberg/instaparse).

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

$parse_as_and_bs = \Khepin\Azay\Parsers::from_grammar($g);

$parse_as_and_bs('aabb');
```

Will produce an output of:

```php
<?php
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

The `t` class is used to represent names and not have them confused with strings since strings which could be the result of your normal parsing. It has the following properties:

- `t` stands for "tag" but is similar to `keyword`s in clojure or `atom`s in erlang
- Cannot be instantiated with `new` 
- Cannot be cloned
- The only way to create a new one is `t::n('a-string-that-is-a-name')`
- `t::n('abc') === t::n('abc') // true`

They can also be used as simple functions:

```php
<?php
$a = ['abc' => 'Starts the alphabet', 'xyz' => 'finishes it.'];

echo t::n('abc')($a); // Starts the alphabet

$s = new \splObjectStorage;
$a[t::n('jkl')] = 'is in the middle';
echo t::n('jkl')($s); // is in the middle
```

The full class name is `\Khepin\Azay\t`

## Rules for writing a grammar:

| Meaning               | Notations         | Example                   |
|---------------        |-------------------|-----------                |
| Rule                  | = : :: :=         | S := B                    |
| Comment               | //                | // Comments only work at the beginning of the line |
| Or                    | |                 | S = "a" | b               |
| And                   | (whitespace)      | S = "a" "b"               |
| Group                 | ( ... )           | (A | B) C                 |
| Optional              | ?                 | A?                        | 
| One or more           | +                 | A+                        | 
| Zero or more          | *                 | A*                        | 
| String                | "..." '...'       | S = "a" 'b'               |
| Regex                 | #"..." #'...'     | S= #'[0-9]*'              |
| End of input          | ε                 | S = "xyz" ε               |
| Hide output           | <...>             | S = <whitespace>          |
| Hide tag              | <...>             | <S> = A*                  |
| Lookahead             | &                 | S = &"a" "alpha" | "beta" |
| Negative Lookahead    | !                 | S = !"a" "beta" | "alpha" |

Rules cannot span multiple lines.

**And** has priority over **Or** so A | B C means A or (B and C). You can use groups to force associativity.

The `"` and `'` characters can be escaped (eg: `\'` in strings and regexes if needed.

## Usage

You've already seen how to create and use a simple parser. Here's how to create a small compiler for it:


```php
<?php
use Khepin\Azay\t;

$parse_tree = [
    [t::n('S'),
        [t::n('ABC'),
            [t::n('A'), 'a', 'a'],
            [t::n('B'), 'b', 'b','b'],
            [t::n('C'), 'c', 'c']
        ]
    ]
];

$transforms = new \splObjectStorage;
$transforms[t::n('A')] = function($first, $second) {
    return $first . ',' . $second;
};
$transforms[t::n('B')] = function($first, $second, $third) {
    return $first.'.'.$second.'.'.$third;
};
$transforms[t::n('C')] = function() {
    return join('|', func_get_args());
};
$transforms[t::n('AB')] = function($as, $bs, $cs) {
    return $bs . $cs . $as;
};

$compiled = \Khepin\Azay\Tree::transform($parse_tree, $transforms);

// => [t::n('S'), 'b.b.bc|ca,a']
```

Any tag for which no transform is specified is just returned as is.

## More advanced examples

The examples folder contains an example of an XML and a JSON parsing grammar. The XML one is not complete as it only contains basic tags declaration and use. No document type or CDATA attribute.
