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

require '../Matcher/Pattern.php';
require '../Matcher/Matcher.php';
/**
 * Description of MyRexExpTest
 *
 * @author Rafael Nájera <rafael.najera@uni-koeln.de>
 */
class MyRegExpTest extends \PHPUnit_Framework_TestCase{
   
    public function testConcatConditions(){
        $pattern = (new Pattern())->withTokenSeries(['a', 'b', 'c']);
        
        $matcher = new Matcher($pattern);
        
        $matcher->match('a');
        $this->assertEquals(false, $matcher->noMatch());
        $matcher->match('b');
        $this->assertEquals(false, $matcher->noMatch());
        $matcher->match('c');
        $this->assertEquals(false, $matcher->noMatch());
        $this->assertEquals(true, $matcher->matchFound());
        $this->assertEquals(['a', 'b', 'c'], $matcher->matched);
        $matcher->match('d');
        $this->assertEquals(false, $matcher->noMatch());
        $this->assertEquals(true, $matcher->matchFound());
        $this->assertEquals(['a', 'b', 'c'], $matcher->matched);
        $matcher->match('e');
        $this->assertEquals(false, $matcher->noMatch());
        $this->assertEquals(true, $matcher->matchFound());
        $this->assertEquals(['a', 'b', 'c'], $matcher->matched);
        
        // Array matching
        $matcher->matchArray(['a', 'b', 'c', 'd', 'e']);
        $this->assertEquals(true, $matcher->matchFound());
        
        $matcher->matchArray(['a', 'b', 'c', 'd', 'e', 'f']);
        $this->assertEquals(true, $matcher->matchFound());
        $this->assertEquals(['a', 'b', 'c'], $matcher->matched);
        
        $matcher->matchArray(['a', 'b', 'd', 'd', 'e']);
        $this->assertEquals(false, $matcher->matchFound());
        
    }
    
    public function testAlternativesMatch(){
        $pattern = (new Pattern())
                ->withTokenSeries(['a', 'b'])
                ->withTokenAlternatives(['c', 'd'])
                ->withTokenSeries(['e']);
        
        $matcher = new Matcher($pattern);
        
        $matcher->matchArray(['a', 'b', 'c', 'e']);
        $this->assertEquals(true, $matcher->matchFound());
        $matcher->matchArray(['a', 'b', 'd',  'e']);
        $this->assertEquals(true, $matcher->matchFound());
        $matcher->matchArray(['a', 'b', 'e']);
        $this->assertEquals(false, $matcher->matchFound());
        $matcher->matchArray(['a', 'b', 'f']);
        $this->assertEquals(false, $matcher->matchFound());
        $matcher->matchArray(['a', 'b',  'c', 'd', 'e']);
        $this->assertEquals(false, $matcher->matchFound());
    }
    
    public function testCallback(){
        $callBackCalled = false;
        $matchedArray = [];
        $callback = function ($matched) use (&$callBackCalled, &$matchedArray) {
                $callBackCalled = true;
                $matchedArray = [];
                $matchedArray = array_merge($matchedArray, $matched);
                return $matched;
            };
        $pattern = (new Pattern())->withTokenSeries(['a', 'b', 'c'])
                ->withCallback($callback);
        
        $path = new Matcher($pattern);
        
        $path->matchArray(['a', 'b', 'c', 'e']);
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals(true, $callBackCalled);
        $this->assertEquals(['a', 'b', 'c'], $matchedArray);
        
    }
    
    public function testCallBack2(){
        $pattern = (new Pattern())->withTokenSeries(['a', 'b', 'c'])
                    ->withCallback( 
                        function ($m) {
                            return [implode($m)];
                        });
        
        $matcher = new Matcher($pattern);
        $matcher->matchArray(['a', 'b', 'c', 'e']);
        $this->assertEquals(true, $matcher->matchFound());
        $this->assertEquals(['abc'], $matcher->matched);
    }
    
    public function testAddPattern(){
        $abcPattern = (new Pattern())->withTokenSeries(['a', 'b', 'c'])->withCallback( 
                        function ($m) {
                            array_pop($m);
                            array_pop($m);
                            array_pop($m);
                            array_push($m, 'Got abc');
                            return $m;});
        
        
        $dePattern = (new Pattern())->withTokenSeries(['d', 'e'])->withCallback( 
                        function ($m) {
                            array_pop($m);
                            array_pop($m);
                            array_push($m, 'Got de');
                            return $m;});
        $pattern = $abcPattern
                ->withAddedPattern($dePattern)
                ->withAddedPattern($abcPattern);
        
        $matcher = new Matcher($pattern);
        
        $matcher->matchArray(['a', 'b', 'c', 'd', 'e', 'a', 'b', 'c']);
        $this->assertEquals(true, $matcher->matchFound());
        $this->assertEquals(['Got abc', 'Got de', 'Got abc'], $matcher->matched);
    }
    
    public function testAddedPatternZeroOrMore(){
        $subP = (new Pattern())->withTokenSeries(['c', 'd'])
                ->withCallback( function($m){
                     array_pop($m);
                            array_pop($m);
                            array_push($m, 'cd');
                            return $m;
                });
        
        $pattern = (new Pattern())->withTokenSeries(['a', 'b'])
                ->withAddedPatternZeroOrMore($subP)
                ->withTokenSeries(['e']);

        $matcher = new Matcher($pattern);
        $matcher->matchArray(['a', 'b', 'e']);
        $this->assertEquals(true, $matcher->matchFound());
        $this->assertEquals(['a', 'b', 'e'], $matcher->matched);
        
        $matcher->matchArray(['a', 'b', 'c', 'd', 'e']);
        $this->assertEquals(true, $matcher->matchFound());
        $this->assertEquals(['a', 'b', 'cd', 'e'], $matcher->matched);
        
        $matcher->matchArray(['a', 'b', 'c', 'd', 'c', 'd', 'e']);
        $this->assertEquals(true, $matcher->matchFound());
        $this->assertEquals(['a', 'b', 'cd', 'cd', 'e'], $matcher->matched);
    }
    
    public function testZeroOrOneMatch(){
        
        $subP = (new Pattern())->withTokenSeries(['c', 'd'])
                ->withCallback( function($m){
                     array_pop($m);
                            array_pop($m);
                            array_push($m, 'cd');
                            return $m;
                });
        $pattern = (new Pattern())
                  ->withTokenSeries(['a', 'b'])
                  ->withAddedPatternZeroOrOne($subP)
                  ->withTokenSeries(['e']);

        $matcher = new Matcher($pattern);
        $matcher->matchArray(['a', 'b', 'c', 'd', 'e']);
        $this->assertEquals(true, $matcher->matchFound());
        $this->assertEquals(['a', 'b', 'cd', 'e'], $matcher->matched);
        
        $matcher->matchArray(['a', 'b', 'e']);
        $this->assertEquals(true, $matcher->matchFound());
        $this->assertEquals(['a', 'b', 'e'], $matcher->matched);
        $matcher->matchArray(['a', 'b', 'f']);
        $this->assertEquals(false, $matcher->matchFound());
        $matcher->matchArray(['a', 'b',  'c', 'f']);
        $this->assertEquals(false, $matcher->matchFound());
        $matcher->matchArray(['a', 'b',  'c', 'd', 'c', 'd', 'f']);
        $this->assertEquals(false, $matcher->matchFound());
    }
    
    public function testOneOrMoreMatch(){
        
         $subP = (new Pattern())->withTokenSeries(['c', 'd'])
                ->withCallback( function($m){
                     array_pop($m);
                            array_pop($m);
                            array_push($m, 'cd');
                            return $m;
                });
                
        $pattern = (new Pattern())->withTokenSeries(['a', 'b'])
                ->withAddedPatternOneOrMore($subP)
                ->withTokenSeries(['e']);

        $matcher = new Matcher($pattern);
        $matcher->matchArray(['a', 'b', 'c', 'd', 'e']);
        $this->assertEquals(true, $matcher->matchFound());
        $this->assertEquals(['a', 'b', 'cd', 'e'], $matcher->matched);

        $matcher->matchArray(['a', 'b', 'c', 'd', 'c', 'd', 'e']);
        $this->assertEquals(true, $matcher->matchFound());
        $this->assertEquals(['a', 'b', 'cd', 'cd', 'e'], $matcher->matched);
        
        $matcher->matchArray(['a', 'b', 'c', 'd', 'c', 'd', 'c', 'd', 'e']);
        $this->assertEquals(true, $matcher->matchFound());
        $this->assertEquals(['a', 'b', 'cd', 'cd', 'cd', 'e'], $matcher->matched);
        
        $matcher->matchArray(['a', 'b', 'e']);
        $this->assertEquals(false, $matcher->matchFound());
        $matcher->matchArray(['a', 'b', 'f']);
        $this->assertEquals(false, $matcher->matchFound());
        $matcher->matchArray(['a', 'b',  'c', 'd', 'c']);
        $this->assertEquals(false, $matcher->matchFound());
     
    }
    
    public function testEOF(){
        $pattern = (new Pattern())->withTokenSeries(['a', 'b', Token::EOF]);
        
        $matcher = new Matcher($pattern);
        
        $matcher->matchArray(['a', 'b']);
        $this->assertEquals(false, $matcher->matchFound());
        
        $matcher->matchArray(['a', 'b', Token::EOF]);
        $this->assertEquals(true, $matcher->matchFound());
    }
}




function printArrayOfArrays($someArray){
    print count($someArray) . " element(s):\n";
    foreach ($someArray as $keyOuterArray => $innerArray){
        foreach (array_keys($innerArray) as $keyInnerArray){
            print " $keyOuterArray, $keyInnerArray\n";
        }
    }
}