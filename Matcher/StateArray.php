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

require_once 'State.php';

/**
 * An array of states
 * 
 * Static functions to build arrays of states
 *
 * @author Rafael Nájera <rafael.najera@uni-koeln.de>
 */
class StateArray {

    /**
     * 
     * @param State[] $states
     * @param Token[] $tokens
     * @return State[]
     */
    public static function addConcatConditions($states, $tokens){
        return self::addStates($states, self::concatConditions($tokens));
    }
    
    public static function concatConditions($tokens){
        $newStates = [];
        $nStates = 0;
        foreach ($tokens as $t){
            $newStates[$nStates] = new State([ new Condition($t, $nStates+1)]);
            $nStates++;
        }
        $newStates[$nStates-1]->conditions[0]->nextState =  State::MATCH;
        return $newStates;
    }

    public static function alternativeConditions($tokens){
        $conditions = [];
        foreach ($tokens as $t){
            $conditions[] = new Condition($t, State::MATCH);
        }
        return [ new State($conditions)];
    }

    public static function addAlternativeConditions($states, $tokens){
        return self::addStates($states, self::alternativeConditions($tokens));
    }
    
    public static function addStatesZeroOrOne($s1, $s2){
        if (count($s2) == 0){
            return $s1;
        }
        $s2[0]->conditions[] = new Condition('', State::MATCH);
        
        return self::addStates($s1, $s2);
    }
    
    public static function addStatesZeroOrMore($s1, $s2){
        if (count($s2) == 0){
            return $s1;
        }
        foreach($s2 as $s){
            foreach($s->conditions as $c){
                if ($c->nextState === State::MATCH){
                    $c->nextState = State::INIT;
                }
            }
        }

        $s2[0]->conditions[] = new Condition('', State::MATCH);
        
        return self::addStates($s1, $s2);
    }
    
    public static function addStatesOneOrMore($s1, $s2){
        if (count($s2) == 0){
            return $s1;
        }
        $newState = count($s2);
        foreach($s2 as $s){
            foreach($s->conditions as $c){
                if ($c->nextState === State::MATCH){
                    $c->nextState = $newState;
                }
            }
        }
        
        $s2[] = new State($s2[0]->conditions);
        
        $s2[$newState]->conditions[] = new Condition('', State::MATCH);
        
        return self::addStates($s1, $s2);
        
    }


    public static function addStates($s1, $s2){
        
        if (count($s2) == 0){
            return $s1;
        }
        
        $nStates = count($s1);
        
        foreach ($s1 as $st){
            foreach ($st->conditions as $c){
                if ($c->nextState === State::MATCH){
                    $c->nextState = $nStates;
                }
            }
        }

        foreach ($s2 as $s){
            foreach ($s->conditions as $c){
                if ($c->nextState !== State::MATCH){
                    $c->nextState += $nStates;
                }
            }
            // This will put EXACTLY the same State objects of $s2 into $s1
            // i.e., $s1 will have the same actual objects of which
            // $s2 is composed
            $s1[] = $s;
        }
        
        return $s1;
        
    }
}