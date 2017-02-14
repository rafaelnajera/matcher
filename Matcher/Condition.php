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

require_once 'Token.php';

/**
 * Description of Condition
 *
 * @author Rafael Nájera <rafael.najera@uni-koeln.de>
 */
class Condition {
    /**
     *
     * @var any or Token  the token
     * 
     */
    var $token;
    var $nextState;
    
    
    /**
     * Initializes the condition with the given token and nextState
     * The given token can be of type Token, in which case matching will
     * be done using the method Token::matches.  In any other case
     * matching is done by strict comparison (===)
     * @param string|Token $t
     * @param int $ns
     */
    public function __construct($t, $ns) {
        $this->token = $t;
        $this->nextState = $ns;
    }
    public function match($t){
        if ($this->token instanceof Token){
            if ($this->token->matches($t)){
                return true;
            }
        } 
        else {
            if ($this->token === $t){
                return true;
            }
        }
        return false;
    }
    
    public function isTokenEmpty(){
        return $this->token === Token::NONE;
    }
}