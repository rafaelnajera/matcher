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

require_once 'util.php';
require_once 'State.php';

class Pattern {
    
    /**
     *
     * @var array 
     */
    public $states;
    
   /**
     *
     * @var int
     */
    private $currentState;
    
    /**
     * NoMatch flag
     * 
     * The flag is set when it has been determined that the pattern
     * did not match the input. 
     * 
     * When noMatch is false, the pattern may or may not have been completely 
     * matched with the input. 
     * 
     * 
     * @var boolean
     */
    private $noMatch;
    
    /**
     * Holds the information from the matched tokens
     * @var array
     */
    var $matched;

    /**
     * Callbacks for different state transitions
     * 
     * $callable[i][j] ==> callable to be called in the transition from
     *       state i to state j
     * 
     * @var callable[][]
     */
    public $callbacks;
    
    public function __construct() {
        $this->states =[];
        $this->callbacks = [];
        $this->reset();
    }
    
    public function reset(){
        $this->noMatch = false;
        $this->currentState = State::INIT;
        $this->matched =  [];
    }
    
    public function noMatch(){
        return $this->noMatch;
    }
    
    public function matchFound(){
        return ($this->currentState===State::MATCH);
    }
    
    /**
     * Set the pattern's match callback
     *  
     *  @param \callable $f
     */
    public function withCallback(callable $f){
        $copy = clone $this;
        foreach ($this->states as $key => $s){
            foreach ($s->conditions as $c){
                if ($c->nextState === State::MATCH){
                    $copy->callbacks[$key][State::MATCH] = $f;
                }
            }
        }
        return $copy;
    }
    
    /**
     * Processes one token and changes the state of
     * the path accordingly. Returns true if the given token
     * did not cause the path go in an unmatched state.
     * 
     * @param any $t  It can be of any type, the conditions used to build
     *                the path should know what to do with it.
     * @return boolean
     */
    public function match($t){
        if ($this->currentState===State::MATCH){
            return true;
        }
        if ($this->noMatch){
            return false;
        }
        $advancing = true;
        
        while ($advancing){
            $hasEmpty = false;
            foreach ($this->states[$this->currentState]->conditions as $condition){
                if ($condition->isTokenEmpty()){
                    // Move on to next state and check again
                    $this->callCallbacks($this->currentState, $condition->nextState);
                    $this->currentState = $condition->nextState;
                    
                    $hasEmpty = true;
                    break;
                } else {
                    if ($condition->match($t)){
                        $this->matched[] = $condition->matched($t);
                        $this->callCallbacks($this->currentState, $condition->nextState);
                        $this->currentState = $condition->nextState;
                        return true;
                    }
                }
                
            }
            $advancing = $hasEmpty;
        }
        // A no-match is confirmed
        $this->currentState = 0;
        $this->noMatch = true;
        $this->matched = [];
        return false;
    }
    
    /**
     * Tries to match an array of tokens.
     * 
     * This resets the internal state of the path.
     * 
     * @param array $tokens
     * @return boolean
     */
    public function matchArray(array $tokens){
        
        $this->reset();
        foreach($tokens as $t){
            if (!$this->match($t)){
                return false;
            }
        }
        return true;
    }
    
    public function withTokenSeries(array $tokens){
        $p = new Pattern();
        
        $newStates = [];
        $nStates = 0;
        foreach ($tokens as $t){
            $newStates[$nStates] = new State([ new Condition($t, $nStates+1)]);
            $nStates++;
        }
        $newStates[$nStates-1]->conditions[State::INIT]->nextState =  State::MATCH;
        $p->states = $newStates;
        return $this->withAddedPattern($p);
    }
    
    public function withTokenAlternatives(array $tokens){
        $p = new Pattern();
        $conditions = [];
        foreach ($tokens as $t){
            $conditions[] = new Condition($t, State::MATCH);
        }
        $p->states[] = new State($conditions);

        return $this->withAddedPattern($p);
    }
    
    public function withAddedPattern(Pattern $p){
        $copy = clone $this;
        if (count($p->states) == 0){
            return $copy;
        }
        
        $p2 = clone $p;
        $nStates = count($copy->states);
        // Point match conditions to the first state after the
        // states of $p1
        foreach ($copy->states as $key => $st){
            foreach ($st->conditions as $c){
                if ($c->nextState === State::MATCH){
                    $c->nextState = $nStates;
                    // redirect the callbacks as well
                    if (isset($copy->callbacks[$key][State::MATCH])){
                        $copy->callbacks[$key][$nStates] = 
                                $copy->callbacks[$key][State::MATCH];
                        unset($copy->callbacks[$key][State::MATCH]);
                    }   
                }
            }
        }
        
        // Append the states of $p2 at the end of $p1
        foreach ($p2->states as $key => $s){
            foreach ($s->conditions as $c){
                if ($c->nextState !== State::MATCH){
                    $c->nextState += $nStates;
                }
            }
            
            $copy->states[] = clone $s;
            // Copy the callbacks from this state as well
            if (isset($p2->callbacks[$key])){
                foreach ($p2->callbacks[$key] as $k2 => $cb){
                    if ($k2 !== State::MATCH){
                        $copy->callbacks[$key + $nStates][$k2 + $nStates] = 
                                $p2->callbacks[$key][$k2];
                    } 
                    else {
                        $copy->callbacks[$key + $nStates][State::MATCH] = 
                                $p2->callbacks[$key][State::MATCH];
                    }
                }
            }
        }
       
        return $copy;
    }
    
    public function withAddedPatternZeroOrMore(Pattern $p){
        $p2 = clone $p;
        
        if (count($p2->states) === 0){
            return clone $this;
        }
        
        // Point the MATCH state back to the initial state
        $s2 = $p2->states;
        foreach($s2 as $s){
            foreach($s->conditions as $c){
                if ($c->nextState === State::MATCH){
                    $c->nextState = State::INIT;
                }
            }
        }
        
        // Add a match condition to the initial state
        $s2[State::INIT]->conditions[] = new Condition(Token::NONE, State::MATCH);
        
        // re-direct the match callbacks to the initial state
        foreach ($p2->callbacks as $key => $cb){
            if (isset($p2->callbacks[$key][State::MATCH])){
                $p2->callbacks[$key][State::INIT] = $p2->callbacks[$key][State::MATCH];
                unset($p2->callbacks[$key][State::MATCH]);
            }
        }
        
        // Add the constructed pattern and return
        return $this->withAddedPattern($p2);
    }
    
    public function withAddedPatternZeroOrOne(Pattern $p){
        $p2 = clone $p;
        
        if (count($p2->states) === 0){
            return clone $this;
        }
        // No need to redirect the match state.
        // Add a match condition to the initial state
        $p2->states[State::INIT]->conditions[] = new Condition(Token::NONE, State::MATCH);
        
        // No need to redirect match callbacks.
        
        // Add the constructed pattern and return
        return $this->withAddedPattern($p2);
        
    }
    
    public function withAddedPatternOneOrMore(Pattern $p){
        
        return $this->withAddedPattern($p)->withAddedPatternZeroOrMore($p);
    }
    
    public function __clone() {
        $this->states = array_clone($this->states);
        
    }
    
    /**
     * Calls callbacks for the given state number
     * 
     * @param int $stateNumber
     */
    private function callCallbacks(int $from, int $to){
        if (isset($this->callbacks[$from][$to]) && 
                is_callable($this->callbacks[$from][$to])){
            $this->matched = call_user_func($this->callbacks[$from][$to], $this->matched);
        }
    }
    
    
}
