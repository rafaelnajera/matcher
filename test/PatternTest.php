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
/**
 * Description of MyRexExpTest
 *
 * @author Rafael Nájera <rafael.najera@uni-koeln.de>
 */
class MyRegExpTest extends \PHPUnit_Framework_TestCase{
   
    public function testConcatConditions(){
        $path = (new Pattern())->withTokenSeries(['a', 'b', 'c']);
        
        $path->match('a');
        $this->assertEquals(false, $path->noMatch());
        $path->match('b');
        $this->assertEquals(false, $path->noMatch());
        $path->match('c');
        $this->assertEquals(false, $path->noMatch());
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals(['a', 'b', 'c'], $path->matched);
        $path->match('d');
        $this->assertEquals(false, $path->noMatch());
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals(['a', 'b', 'c'], $path->matched);
        $path->match('e');
        $this->assertEquals(false, $path->noMatch());
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals(['a', 'b', 'c'], $path->matched);
        
        // Array matching
        $m = $path->matchArray(['a', 'b', 'c', 'd', 'e']);
        $this->assertEquals(true, $path->matchFound());
        
        $m = $path->matchArray(['a', 'b', 'c', 'd', 'e', 'f']);
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals(['a', 'b', 'c'], $path->matched);
        
        $m = $path->matchArray(['a', 'b', 'd', 'd', 'e']);
        $this->assertEquals(false, $path->matchFound());
        
    }
    
    public function testAlternativesMatch(){
        $path = (new Pattern())
                ->withTokenSeries(['a', 'b'])
                ->withTokenAlternatives(['c', 'd'])
                ->withTokenSeries(['e']);
        
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
        $callBackCalled = false;
        $matchedArray = [];
        $cb = function ($m) use (&$callBackCalled, &$matchedArray) {
                $callBackCalled = true;
                $matchedArray = [];
                $matchedArray = array_merge($matchedArray, $m);
                return $m;
            };
        $path = (new Pattern())->withTokenSeries(['a', 'b', 'c'])
                ->withCallback($cb);
        
        $path->matchArray(['a', 'b', 'c', 'e']);
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals(true, $callBackCalled);
        $this->assertEquals(['a', 'b', 'c'], $matchedArray);
        
    }
    
    public function testCallBack2(){
        $path = (new Pattern())->withTokenSeries(['a', 'b', 'c'])
                    ->withCallback( 
                        function ($m) {
                            return [implode($m)];
                        });
        
        $path->matchArray(['a', 'b', 'c', 'e']);
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals(['abc'], $path->matched);
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
        $path = $abcPattern
                ->withAddedPattern($dePattern)
                ->withAddedPattern($abcPattern);
        
        
        $path->matchArray(['a', 'b', 'c', 'd', 'e', 'a', 'b', 'c']);
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals(['Got abc', 'Got de', 'Got abc'], $path->matched);
    }
    
    public function testAddedPatternZeroOrMore(){
        $subP = (new Pattern())->withTokenSeries(['c', 'd'])
                ->withCallback( function($m){
                     array_pop($m);
                            array_pop($m);
                            array_push($m, 'cd');
                            return $m;
                });
        
        $p = (new Pattern())->withTokenSeries(['a', 'b'])
                ->withAddedPatternZeroOrMore($subP)
                ->withTokenSeries(['e']);

        $p->matchArray(['a', 'b', 'e']);
        $this->assertEquals(true, $p->matchFound());
        $this->assertEquals(['a', 'b', 'e'], $p->matched);
        
        $p->matchArray(['a', 'b', 'c', 'd', 'e']);
        $this->assertEquals(true, $p->matchFound());
        $this->assertEquals(['a', 'b', 'cd', 'e'], $p->matched);
        
        $p->matchArray(['a', 'b', 'c', 'd', 'c', 'd', 'e']);
        $this->assertEquals(true, $p->matchFound());
        $this->assertEquals(['a', 'b', 'cd', 'cd', 'e'], $p->matched);
    }
    
    public function testZeroOrOneMatch(){
        
        $subP = (new Pattern())->withTokenSeries(['c', 'd'])
                ->withCallback( function($m){
                     array_pop($m);
                            array_pop($m);
                            array_push($m, 'cd');
                            return $m;
                });
        $path = (new Pattern())
                  ->withTokenSeries(['a', 'b'])
                  ->withAddedPatternZeroOrOne($subP)
                  ->withTokenSeries(['e']);

        
        $path->matchArray(['a', 'b', 'c', 'd', 'e']);
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals(['a', 'b', 'cd', 'e'], $path->matched);
        
        $path->matchArray(['a', 'b', 'e']);
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals(['a', 'b', 'e'], $path->matched);
        $path->matchArray(['a', 'b', 'f']);
        $this->assertEquals(false, $path->matchFound());
        $path->matchArray(['a', 'b',  'c', 'f']);
        $this->assertEquals(false, $path->matchFound());
        $path->matchArray(['a', 'b',  'c', 'd', 'c', 'd', 'f']);
        $this->assertEquals(false, $path->matchFound());
    }
    
    public function testOneOrMoreMatch(){
        
         $subP = (new Pattern())->withTokenSeries(['c', 'd'])
                ->withCallback( function($m){
                     array_pop($m);
                            array_pop($m);
                            array_push($m, 'cd');
                            return $m;
                });
                
        $path = (new Pattern())->withTokenSeries(['a', 'b'])
                ->withAddedPatternOneOrMore($subP)
                ->withTokenSeries(['e']);

//        print "\nFull path states:\n";
//        print_r($path->states);
//        
//        print "\nFull path callbacks : \n";
//        printArrayOfArrays($path->callbacks);
//        
        
        $path->matchArray(['a', 'b', 'c', 'd', 'e']);
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals(['a', 'b', 'cd', 'e'], $path->matched);

        $path->matchArray(['a', 'b', 'c', 'd', 'c', 'd', 'e']);
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals(['a', 'b', 'cd', 'cd', 'e'], $path->matched);
        
        $path->matchArray(['a', 'b', 'c', 'd', 'c', 'd', 'c', 'd', 'e']);
        $this->assertEquals(true, $path->matchFound());
        $this->assertEquals(['a', 'b', 'cd', 'cd', 'cd', 'e'], $path->matched);
        
        $path->matchArray(['a', 'b', 'e']);
        $this->assertEquals(false, $path->matchFound());
        $path->matchArray(['a', 'b', 'f']);
        $this->assertEquals(false, $path->matchFound());
        $path->matchArray(['a', 'b',  'c', 'd', 'c']);
        $this->assertEquals(false, $path->matchFound());
     
    }
}

function printArrayOfArrays($a){
    print count($a) . " element(s):\n";
    foreach ($a as $ka => $b){
        foreach ($b as $kb => $x){
            print " $ka, $kb\n";
        }
    }
}