<?php

/*
 *  Copyright (C) 2016 Universität zu Köln
 *  
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; either version 2
 *  of the License, or (at your option) any later version.
 *   
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *  
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
        
        $path->pushStates(StateArray::concatConditions(['d', 'e']));
        //print_r($path);
        $path->reset();
        $path->match('a');
        $path->match('b');
        $path->match('c');
        $path->match('d');
        $path->match('e');
        $this->assertEquals(false, $path->NoMatch());
        $this->assertEquals(true, $path->matchFound());
        
        $path->reset();
        $path->match('a');
        $path->match('c');
        $this->assertEquals(true, $path->NoMatch());
        $this->assertEquals(false, $path->matchFound());
        
        $path->reset();
        $m = $path->matchArray(['a', 'b', 'c', 'd', 'e']);
        $this->assertEquals(true, $path->matchFound());
        
        $m = $path->matchArray(['a', 'b', 'd', 'd', 'e']);
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
        $path->matchArray(['a', 'b', 'e']);
        $this->assertEquals(true, $path->matchFound());
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
}
