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

/**
 * Matches a series of input token against a Pattern
 *
 * @author Rafael Nájera <rafael.najera@uni-koeln.de>
 */
class Matcher
{

    const VERSION = '0.5';

    const E_NOERROR = 'E_NOERROR';
    const E_NOMATCH = 'E_NOMATCH';
    /**
     *
     * @var int
     */
    private $currentState;


    private $states;
    private $callbacks;




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
     * (can be changed by callbacks)
     * @var array
     */
    public $matched;

    /**
     * Holds the information from the matched tokens
     * (not modified by callbacks)
     * @var array
     */
    public $actualMatched;

    /**
     * An error code if the matcher is in NoMatch state
     * @var string
     */
    public $error;

    public function __construct(Pattern $pattern)
    {
        $this->states = $pattern->getStates();
        $this->callbacks = $pattern->getCallbacks();
        $this->reset();
    }

    public function reset()
    {
        $this->noMatch = false;
        $this->currentState = State::INIT;
        $this->matched =  [];
        $this->actualMatched = [];
        $this->error = self::E_NOERROR;
    }

    public function noMatch()
    {
        return $this->noMatch;
    }

    public function matchFound()
    {
        return ($this->currentState===State::MATCH_FOUND);
    }

    /**
     * Processes one input token and changes the state of
     * the pattern accordingly. Returns true if the given input
     * did not cause the pattern go in an unmatched state.
     *
     * @param any $input  It can be of any type, the conditions used to build
     *                the pattern should know what to do with it.
     * @return boolean
     */
    public function match($input)
    {
        if ($this->currentState===State::MATCH_FOUND) {
            return true;
        }
        if ($this->noMatch) {
            return false;
        }
        $advancing = true;

        while ($advancing) {
            $hasEmpty = false;
            foreach ($this->states[$this->currentState]->conditions as $condition) {
                if ($condition->isTokenEmpty()) {
                    // Move on to next state and check again
                    $this->callCallbacks($this->currentState, $condition->nextState);
                    $this->currentState = $condition->nextState;

                    $hasEmpty = true;
                    break;
                }
                if ($condition->match($input)) {
                    $this->matched[] = $condition->matched($input);
                    $this->actualMatched[] = $condition->matched($input);
                    $this->callCallbacks($this->currentState, $condition->nextState);
                    $this->currentState = $condition->nextState;
                    return true;
                }
            }
            $advancing = $hasEmpty;
        }
        // A no-match is confirmed
        $this->error = self::E_NOMATCH;
        $this->noMatch = true;
        $this->currentState = State::NO_MATCH;
        return false;
    }

    /**
     * Tries to match an array of tokens.
     *
     * Calls the match() method for every element of the given
     * array.
     *
     * @param array $tokens
     * @param boolean $reset If true, reset the matcher before starting to match
     * @return boolean
     */
    public function matchArray(array $tokens, $reset = true)
    {
        if ($reset) {
            $this->reset();
        }
        foreach ($tokens as $t) {
            if (!$this->match($t)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Calls callbacks for the given state number
     *
     * @param int $stateNumber
     */
    private function callCallbacks(int $initialState, int $endState)
    {
        if (isset($this->callbacks[$initialState][$endState]) &&
                is_callable($this->callbacks[$initialState][$endState])) {
            $this->matched = call_user_func($this->callbacks[$initialState][$endState], $this->matched);
        }
    }
}
