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
 * A token interface
 * 
 * 
 *
 * @author Rafael Nájera <rafael.najera@uni-koeln.de>
 */

interface Token {
    const NONE = '';
    // an alias
    const EMPTY_TOKEN = self::NONE;
    
    /**
     * Returns true is the token matches the given variable $t2
     * 
     * There is no restriction on the type of $t2 as long as 
     * the Token class know what to do with it.
     * 
     * @param any $t2
     * @return boolean 
     */
    public function matches($t2);
    
    /**
     * Returns information about the matched token. 
     * 
     * There is no restriction on the type of $t2 as long as 
     * the Token class knows how to get relevant information out of it.
     * 
     * @param type $t2
     */
    public function matched($t2);
}
