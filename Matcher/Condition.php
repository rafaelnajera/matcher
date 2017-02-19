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
 * Condition: a token and next state
 *
 * @author Rafael Nájera <rafael.najera@uni-koeln.de>
 */
class Condition
{
    /**
     *
     * @var any|Token
     *
     */
    public $token;

    /**
     *
     * @var int
     */
    public $nextState;


    /**
     * Initializes the condition with the given token and nextState
     * The given token can be of type Token, in which case matching will
     * be done using the method Token::matches.  In any other case
     * matching is done by strict comparison (===)
     * @param any|Token $token
     * @param int $nextState
     */
    public function __construct($token, int $nextState)
    {
        $this->token = $token;
        $this->nextState = $nextState;
    }

    /**
     * Returns true is the given token matches the Condition's token
     *
     * @param any|Token $input
     * @return boolean
     */
    public function match($input)
    {
        if ($this->token instanceof Token) {
            if ($this->token->matches($input)) {
                return true;
            }
            return false;
        }
        
        if ($this->token === $input) {
            return true;
        }
        return false;
    }

    /**
     * Returns information about the matched token.
     *
     * If the condition's token implements the Token interface
     * the token's matched() method will be called.
     * If not, returns a copy of the token
     *
     * @return any
     */
    public function matched($input)
    {
        if ($this->token instanceof Token) {
            return $this->token->matched($input);
        }
        
        return $input;
    }

    /**
     * Returns true is the token is the empty token
     *
     * @return boolean
     */
    public function isTokenEmpty()
    {
        return $this->token === Token::NONE;
    }

    /**
     * Clone
     *
     * Makes sure that $this->token is cloned, not just copied
     */
    public function __clone()
    {
        if (is_object($this->token)) {
            $this->token = clone $this->token;
        }
    }

    public function __toString()
    {
        return (string) $this->token;
    }
}
