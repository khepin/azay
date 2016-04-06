<?php
namespace Khepin\Partibus;

class CompilerTest extends \PHPUnit_Framework_TestCase {

    function test_single_rule_grammar() {
        $grammar = 'hello = "bob"';
        $parser = Compiler::compile(BnfGrammar::parse($grammar));
        $this->assertEquals($parser(new Input('bob')), [t::n('hello'), 'bob']);

        $grammar = 'hello = "bob" "a"';
        $parser = Compiler::compile(BnfGrammar::parse($grammar));
        $this->assertEquals($parser(new Input('boba')), [t::n('hello'), 'bob', 'a']);

        $grammar = 'hello = "bob" ε';
        $parser = Compiler::compile(BnfGrammar::parse($grammar));
        $this->assertEquals($parser(new Input('bob')), [t::n('hello'), 'bob', [t::n('ε')]]);

        $grammar = 'hello = "bob" | "bib"';
        $parser = Compiler::compile(BnfGrammar::parse($grammar));
        $this->assertEquals($parser(new Input('bob')), [t::n('hello'), 'bob']);
        $this->assertEquals($parser(new Input('bib')), [t::n('hello'), 'bib']);


        $grammar = 'hello = "bob" "bib"';
        $parser = Compiler::compile(BnfGrammar::parse($grammar));
        $this->assertEquals($parser(new Input('bobbib')), [t::n('hello'), 'bob', 'bib']);


        $grammar = 'hello = #"a+"';
        $parser = Compiler::compile(BnfGrammar::parse($grammar));
        $this->assertEquals($parser(new Input('a')), [t::n('hello'), 'a']);
        $this->assertEquals($parser(new Input('aa')), [t::n('hello'), 'aa']);
        $this->assertEquals($parser(new Input('aaa')), [t::n('hello'), 'aaa']);
        $this->assertEquals($parser(new Input('aaaa')), [t::n('hello'), 'aaaa']);


        $grammar = 'hello = <#"a+">';
        $parser = Compiler::compile(BnfGrammar::parse($grammar));
        $this->assertEquals($parser(new Input('aaa')), null);

        $grammar = 'hello = "o" <#"a+">';
        $parser = Compiler::compile(BnfGrammar::parse($grammar));
        $this->assertEquals($parser(new Input('oaaa')), [t::n('hello'), 'o']);

        $grammar = 'hello = "o"? #"a+"';
        $parser = Compiler::compile(BnfGrammar::parse($grammar));
        $this->assertEquals($parser(new Input('oaaa')), [t::n('hello'), 'o', 'aaa']);
        $this->assertEquals($parser(new Input('aaa')), [t::n('hello'), 'aaa']);

        $grammar = 'hello = "o"* #"a+"';
        $parser = Compiler::compile(BnfGrammar::parse($grammar));
        $this->assertEquals($parser(new Input('oaaa')), [t::n('hello'), 'o', 'aaa']);
        $this->assertEquals($parser(new Input('ooaaa')), [t::n('hello'), 'o', 'o', 'aaa']);
        $this->assertEquals($parser(new Input('aaa')), [t::n('hello'), 'aaa']);

        $grammar = 'hello = "o"+ #"a+"';
        $parser = Compiler::compile(BnfGrammar::parse($grammar));
        $this->assertEquals($parser(new Input('oaaa')), [t::n('hello'), 'o', 'aaa']);
        $this->assertEquals($parser(new Input('ooaaa')), [t::n('hello'), 'o', 'o', 'aaa']);
        $failed = false;
        try {
            $parser(new Input('aaa'));
        } catch (ParseException $e) {
            $failed = true;
        }
        $this->assertTrue($failed);

        $grammar = 'hello = &"o" #"oa+"';
        $parser = Compiler::compile(BnfGrammar::parse($grammar));
        $this->assertEquals($parser(new Input('oaaa')), [t::n('hello'), 'oaaa']);

        $grammar = 'hello = !"o" #"ca+"';
        $parser = Compiler::compile(BnfGrammar::parse($grammar));
        $this->assertEquals($parser(new Input('caaa')), [t::n('hello'), 'caaa']);
        $failed = false;
        try {
            $parser(new Input('oaaa'));
        } catch (ParseException $e) {
            $failed = true;
        }
        $this->assertTrue($failed);

        $grammar = 'hello = ("o" #"a+")';
        $parser = Compiler::compile(BnfGrammar::parse($grammar));
        $this->assertEquals($parser(new Input('oaaa')), [t::n('hello'), ['o', 'aaa']]);
    }

    function test_multiple_rules() {
        $grammar = '//
name : firstname <" "> lastname
// A first name starts with a capital letter followed by n small letters
firstname : #"[A-Z][a-z]*"
// A last name is the opposite
lastname  : #"[a-z][A-Z]*"';
        $parser = Compiler::compile(BnfGrammar::parse($grammar));
        $this->assertEquals($parser(new Input('Bill mALLONE')), [t::n('name'), [t::n('firstname'), 'Bill'], [t::n('lastname'), 'mALLONE']]);
    }

    function test_recursive_parser() {
        $grammar = 'a = <ε> | more
more = "a" a';
        $parser = Compiler::compile(BnfGrammar::parse($grammar));
        $this->assertEquals($parser(new Input('aaa')), [t::n('a'), [t::n('more'), "a", [t::n('a'), [t::n('more'), "a", [t::n('a'), [t::n('more'), "a"]]]]]]);
    }

    function test_token_unsplicing() {
        $grammar = 'a = <"("> bloup <")">
<bloup> = "bloup"*';
        $parser = Compiler::compile(BnfGrammar::parse($grammar));
        $this->assertEquals($parser(new Input('()')), null);
        $this->assertEquals($parser(new Input('(bloup)')), [t::n('a'), 'bloup']);
    }

    function test_xml_tag_parsing() {
        $grammar = "tag = <'<'> tagname <ows> (<'/>'> | <'<'> tagname <'>'>)
tagname = #'[A-Za-z]+'
ows = #'\s+'";
        $parser = Compiler::compile(BnfGrammar::parse($grammar));
        $this->assertEquals($parser(new Input('<Hello />')), [t::n('tag'), [t::n('tagname'), 'Hello']]);
    }

    function test_keyword_named_rules() {
        $grammar = "string = 'bob'";
        $parser = Compiler::compile(BnfGrammar::parse($grammar));
        $this->assertEquals($parser(new Input('bob')), [t::n('string'), 'bob']);
    }

    function test_group_modifier() {
        $grammar = "ng = !('a') 'cac'";
        $parser = Compiler::compile(BnfGrammar::parse($grammar));
        $this->assertEquals($parser(new Input('cac')), [t::n('ng'), 'cac']);
    }
}