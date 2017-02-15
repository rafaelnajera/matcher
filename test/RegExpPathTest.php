<?php

/*
 * The MIT License
 *
 * Copyright 2017 Rafael Nájera <rafael.najera@uni-koeln.de>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Matcher;

require '../Matcher/RegExpPath.php';
/**
 * Description of MyRexExpTest
 *
 * @author Rafael Nájera <rafael.najera@uni-koeln.de>
 */
class MyRegExpTest extends \PHPUnit_Framework_TestCase{
    public function testSimplePath(){
        $path = new RegExpPath();
        
        $path->states = [ 
            new State( [ new Condition('a', 1)]), 
            new State( [ new Condition('b', 2)]),
            new State( [ new Condition('c', State::MATCH)])
        ];
        
        
        $path->match('a');
        $this->assertEquals(false, $path->NoMatch());
        $path->match('b');
        $this->assertEquals(false, $path->NoMatch());
        $path->match('c');
        $this->assertEquals(false, $path->NoMatch());
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals(['a', 'b', 'c'], $path->matched);
        $path->reset();
        $path->match('a');
        $this->assertEquals(false, $path->NoMatch());
        $path->match('c');
        $this->assertEquals(true, $path->NoMatch());

    }
    
    public function testConcatConditions(){
        $path = new RegExpPath();
        
        
        $path->pushStates(StateArray::concatConditions(['a', 'b', 'c']));
        
        $path->match('a');
        $this->assertEquals(false, $path->NoMatch());
        $path->match('b');
        $this->assertEquals(false, $path->NoMatch());
        $path->match('c');
        $this->assertEquals(false, $path->NoMatch());
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals(['a', 'b', 'c'], $path->matched);
        // continue matching in non-strict mode
        $path->match('d');
        $this->assertEquals(false, $path->NoMatch());
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals(['a', 'b', 'c'], $path->matched);
        $path->match('e');
        $this->assertEquals(false, $path->NoMatch());
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals(['a', 'b', 'c'], $path->matched);
        
        // Now in strict mode
        $path->pushStates(StateArray::concatConditions(['d', 'e']));
        $path->reset();
        $path->setStrictMode();
        $path->match('a');
        $path->match('b');
        $path->match('c');
        $path->match('d');
        $path->match('e');
        $this->assertEquals(false, $path->NoMatch());
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals(['a', 'b', 'c', 'd', 'e'], $path->matched);
        // Matching an empty token should succeed!
        $path->match(Token::EMPTY_TOKEN);
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals(['a', 'b', 'c', 'd', 'e'], $path->matched);
        // Matching anything else should fail
        $path->match('f');
        $this->assertEquals(true, $path->NoMatch());
        $this->assertEquals(false, $path->matchFound());
        $path->match('g');
        $this->assertEquals(true, $path->NoMatch());
        $this->assertEquals(false, $path->matchFound());
        
        $path->reset();
        $path->match('a');
        $path->match('c');
        $this->assertEquals(true, $path->NoMatch());
        $this->assertEquals(false, $path->matchFound());
        
        // Array matching
        $path->setStrictMode(false);
        $m = $path->matchArray(['a', 'b', 'c', 'd', 'e']);
        $this->assertEquals(true, $path->matchFound());
        
        $m = $path->matchArray(['a', 'b', 'c', 'd', 'e', 'f']);
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals(['a', 'b', 'c', 'd', 'e'], $path->matched);
        
        $m = $path->matchArray(['a', 'b', 'd', 'd', 'e']);
        $this->assertEquals(false, $path->matchFound());
        
        $path->setStrictMode();
        $m = $path->matchArray(['a', 'b', 'c', 'd', 'e']);
        $this->assertEquals(true, $path->matchFound());
        
        $m = $path->matchArray(['a', 'b', 'c', 'd', 'e', 'f']);
        $this->assertEquals(false, $path->matchFound());
        
        
    }
    
    public function testZeroOrOneMatch(){
        $path = new RegExpPath();
        
        $s  = StateArray::concatConditions(['a', 'b']);
        $s  = StateArray::addStatesZeroOrOne($s,
                StateArray::concatConditions(['c', 'd']));
        $s  = StateArray::addStates($s,
                StateArray::concatConditions(['e']));
        $path->pushStates($s);
        
        //print_r($path);
        
        $path->matchArray(['a', 'b', 'c', 'd', 'e']);
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals(['a', 'b', 'c', 'd', 'e'], $path->matched);
        
        $path->matchArray(['a', 'b', 'e']);
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals(['a', 'b', 'e'], $path->matched);
        $path->matchArray(['a', 'b', 'f']);
        $this->assertEquals(false, $path->matchFound());
        $path->matchArray(['a', 'b',  'c', 'f']);
        $this->assertEquals(false, $path->matchFound());
        
        
    }
    
     public function testZeroOrMoreMatch(){
        $path = new RegExpPath();
        
        $s  = StateArray::addConcatConditions([], ['a', 'b']);
        $s  = StateArray::addStatesZeroOrMore($s,
                StateArray::addConcatConditions([], ['c', 'd']));
        $s  = StateArray::addStates($s,
                StateArray::addConcatConditions([], ['e']));
        $path->pushStates($s);
        
        //print_r($path->states);
        
        $path->matchArray(['a', 'b', 'c', 'd', 'e']);
        $this->assertEquals(true, $path->matchFound());
        $path->matchArray(['a', 'b', 'c', 'd', 'c', 'd', 'e']);
        $this->assertEquals(true, $path->matchFound());
        $path->matchArray(['a', 'b', 'c', 'd', 'c', 'd', 'c', 'd', 'e']);
        $this->assertEquals(true, $path->matchFound());
        $path->matchArray(['a', 'b', 'e']);
        $this->assertEquals(true, $path->matchFound());
        $path->matchArray(['a', 'b', 'f']);
        $this->assertEquals(false, $path->matchFound());
        $path->matchArray(['a', 'b',  'c', 'd', 'c']);
        $this->assertEquals(false, $path->matchFound());
        
        
    }
    
    
    public function testOneOrMoreMatch(){
        $path = new RegExpPath();
        
        $s  = StateArray::addConcatConditions([], ['a', 'b']);
        $s  = StateArray::addStatesOneOrMore($s,
                StateArray::addConcatConditions([], ['c', 'd']));
        $s  = StateArray::addConcatConditions($s, [ 25 ]);
        $path->pushStates($s);
        
        //print_r($path->states);
        
        $path->matchArray(['a', 'b', 'c', 'd', 25]);
        $this->assertEquals(true, $path->matchFound());
        $path->matchArray(['a', 'b', 'c', 'd', 'c', 'd',25]);
        $this->assertEquals(true, $path->matchFound());
        $path->matchArray(['a', 'b', 'c', 'd', 'c', 'd', 'c', 'd', 25]);
        $this->assertEquals(true, $path->matchFound());
        $path->matchArray(['a', 'b', 25]);
        $this->assertEquals(false, $path->matchFound());
        $path->matchArray(['a', 'b', 'f']);
        $this->assertEquals(false, $path->matchFound());
        $path->matchArray(['a', 'b',  'c', 'd', 'c']);
        $this->assertEquals(false, $path->matchFound());
     
    }
    
     public function testAlternativesMatch(){
        $path = new RegExpPath();
        
        $s  = StateArray::concatConditions(['a', 'b']);
        $s  = StateArray::addAlternativeConditions($s, ['c', 'd']);
        $s  = StateArray::addConcatConditions($s, ['e']);
        $path->pushStates($s);
        
        //print_r($path->states);
        
        $path->matchArray(['a', 'b', 'c', 'e']);
        $this->assertEquals(true, $path->matchFound());
        $path->matchArray(['a', 'b', 'd',  'e']);
        $this->assertEquals(true, $path->matchFound());
        $path->matchArray(['a', 'b', 'e']);
        $this->assertEquals(false, $path->matchFound());
        $path->matchArray(['a', 'b', 'f']);
        $this->assertEquals(false, $path->matchFound());
        $path->matchArray(['a', 'b',  'c', 'd', 'e']);
        $this->assertEquals(false, $path->matchFound());
    }
    
    public function testCallback(){
        $path = new RegExpPath();
        $path->pushStates(StateArray::concatConditions(['a', 'b', 'c']));
        
        $callBackCalled = false;
        $matchedArray = [];
        $path->setCallback( function ($m) use (&$callBackCalled, &$matchedArray) {
            $callBackCalled = true;
            $matchedArray = [];
            $matchedArray = array_merge($matchedArray, $m);
            return $m;
        });
        
        $path->matchArray(['a', 'b', 'c', 'e']);
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals(true, $callBackCalled);
        $this->assertEquals(['a', 'b', 'c'], $matchedArray);
        
        $callBackCalled = false;
        $matchedArray = [];
        $path->setStrictMode();
        $path->matchArray(['a', 'b', 'c', 'e']);
        $this->assertEquals(true, $path->NoMatch());
        $this->assertEquals(false, $callBackCalled);
        $path->matchArray(['a', 'b', 'c']);
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals(true, $callBackCalled);
        $this->assertEquals(['a', 'b', 'c'], $matchedArray);
        $this->assertEquals(['a', 'b', 'c'], $path->matched);
        
    }
    
    public function testCallBack2(){
        $path = new RegExpPath();
        $path->pushStates(StateArray::concatConditions(['a', 'b', 'c']));
        
        $path->setCallback( 
                function ($m) {
                    return implode($m);
                }
        );
        
        $path->matchArray(['a', 'b', 'c', 'e']);
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals('abc', $path->matched);
    }
}
