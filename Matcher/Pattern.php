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

require_once '../vendor/autoload.php';

class Pattern {

    /**
     *
     * @var array
     */
    private $states;

    /**
     * Callbacks for different state transitions
     *
     * $callable[i][j] ==> callable to be called in the transition from
     *       state i to state j
     *
     * @var callable[][]
     */
    private $callbacks;

    public function __construct() {
        $this->states =[];
        $this->callbacks = [];
    }

    /**
     * Set the pattern's match callback
     *
     *  @param \callable $theCallback
     */
    public function withCallback(callable $theCallback){
        $copy = clone $this;
        foreach ($this->states as $key => $s){
            foreach ($s->conditions as $c){
                if ($c->nextState === State::MATCH_FOUND){
                    $copy->callbacks[$key][State::MATCH_FOUND] = $theCallback;
                }
            }
        }
        return $copy;
    }

    public function withTokenSeries(array $tokens){
        $newP = new Pattern();

        $newStates = [];
        $nStates = 0;
        foreach ($tokens as $t){
            $newStates[$nStates] = new State([ new Condition($t, $nStates+1)]);
            $nStates++;
        }
        $newStates[$nStates-1]->conditions[State::INIT]->nextState =  State::MATCH_FOUND;
        $newP->states = $newStates;
        return $this->withAddedPattern($newP);
    }

    public function withTokenAlternatives(array $tokens){
        $newP = new Pattern();
        $conditions = [];
        foreach ($tokens as $t){
            $conditions[] = new Condition($t, State::MATCH_FOUND);
        }
        $newP->states[] = new State($conditions);

        return $this->withAddedPattern($newP);
    }

    public function withAddedPattern(Pattern $pattern){
        $copy = clone $this;
        if (count($pattern->states) == 0){
            return $copy;
        }

        $pCopy = clone $pattern;
        $nStates = count($copy->states);
        // Point match conditions to the first state after the
        // states of $p1
        foreach ($copy->states as $key => $st){
            foreach ($st->conditions as $c){
                if ($c->nextState === State::MATCH_FOUND){
                    $c->nextState = $nStates;
                    // redirect the callbacks as well
                    if (isset($copy->callbacks[$key][State::MATCH_FOUND])){
                        $copy->callbacks[$key][$nStates] =
                                $copy->callbacks[$key][State::MATCH_FOUND];
                        unset($copy->callbacks[$key][State::MATCH_FOUND]);
                    }
                }
            }
        }

        // Append the states of $p2 at the end of $p1
        foreach ($pCopy->states as $key => $s){
            foreach ($s->conditions as $c){
                if ($c->nextState !== State::MATCH_FOUND){
                    $c->nextState += $nStates;
                }
            }

            $copy->states[] = clone $s;
            // Copy the callbacks from this state as well
            if (isset($pCopy->callbacks[$key])){
                foreach ($pCopy->callbacks[$key] as $k2 => $cb){
                    if ($k2 !== State::MATCH_FOUND){
                        $copy->callbacks[$key + $nStates][$k2 + $nStates] =
                                $cb;
                    }
                    else {
                        $copy->callbacks[$key + $nStates][State::MATCH_FOUND] =
                                $pCopy->callbacks[$key][State::MATCH_FOUND];
                    }
                }
            }
        }

        return $copy;
    }

    public function withAddedPatternZeroOrMore(Pattern $pattern){
        $pCopy = clone $pattern;

        if (count($pCopy->states) === 0){
            return clone $this;
        }

        // Point the MATCH state back to the initial state
        $pStates = $pCopy->states;
        foreach($pStates as $s){
            foreach($s->conditions as $c){
                if ($c->nextState === State::MATCH_FOUND){
                    $c->nextState = State::INIT;
                }
            }
        }

        // Add a match condition to the initial state
        $pStates[State::INIT]->conditions[] = new Condition(Token::NONE, State::MATCH_FOUND);

        // re-direct the match callbacks to the initial state
        foreach (array_keys($pCopy->callbacks) as $key){
            if (isset($pCopy->callbacks[$key][State::MATCH_FOUND])){
                $pCopy->callbacks[$key][State::INIT] = $pCopy->callbacks[$key][State::MATCH_FOUND];
                unset($pCopy->callbacks[$key][State::MATCH_FOUND]);
            }
        }

        // Add the constructed pattern and return
        return $this->withAddedPattern($pCopy);
    }

    public function withAddedPatternZeroOrOne(Pattern $pattern){
        $pCopy = clone $pattern;

        if (count($pCopy->states) === 0){
            return clone $this;
        }
        // No need to redirect the match state.
        // Add a match condition to the initial state
        $pCopy->states[State::INIT]->conditions[] = new Condition(Token::NONE, State::MATCH_FOUND);

        // No need to redirect match callbacks.

        // Add the constructed pattern and return
        return $this->withAddedPattern($pCopy);

    }

    public function withAddedPatternOneOrMore(Pattern $pattern){

        return $this->withAddedPattern($pattern)->withAddedPatternZeroOrMore($pattern);
    }

    public function __clone() {
        $this->states = Utilities::array_clone($this->states);

    }

    public function getStates(){
        return $this->states;
    }

    public function getCallbacks(){
        return $this->callbacks;
    }

}
