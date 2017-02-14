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

require_once 'StateArray.php';

class RegExpPath {
    
    /**
     *
     * @var array 
     */
    var $states;
    
    
    /**
     *
     * @var array
     */
    var $matched;
    
    /**
     *
     * @var int
     */
    var $currentState;
    
    /**
     * NoMatch flag
     * 
     * The flag is set when it has been determined that the path
     * did not match the input. 
     * 
     * When noMatch is false, the path may or may not have been completely 
     * matched with the input. 
     * 
     * 
     * @var boolean
     */
    var $noMatch;
    
    /**
     * Strict mode flag
     * 
     * In strict mode once the path is matched, any extra non-empty tokens
     * make the path be flagged as unmatched
     * @var boolean
     */
    var $strictMode;
    
    
    public function __construct() {
        $this->states =[];
        $this->strictMode = false;
        $this->reset();
    }
    
    public function reset(){
        $this->noMatch = false;
        $this->currentState = State::INIT;
        $this->matched =  [];
    }
    
    public function NoMatch(){
        return $this->noMatch;
    }
    
    public function setStrictMode($flag = true){
        $this->strictMode = $flag;
    }
    
    public function matchFound(){
        return ($this->currentState===State::MATCH);
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
            if ($this->strictMode && $t !== Token::NONE){
                $this->noMatch = true;
                $this->currentState = 0;
                return false;
            }
            else{
                return true;
            }
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
                    $this->currentState = $condition->nextState;
                    // The empty condition effectively prevents any further
                    // condition from being checked. 
                    $hasEmpty = true;
                    break;
                } else {
                    if ($condition->match($t)){
                        $this->matched[] = $condition->matched($t);
                        $this->currentState = $condition->nextState;
                        // add callback here
                        return true;
                    }
                }
                
            }
            $advancing = $hasEmpty;
        }
        $this->noMatch = true;
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
        return $this->match(Token::NONE);
    }
    
    public function pushStates($states){
        $this->states = StateArray::addStates($this->states, $states);
    }

}
