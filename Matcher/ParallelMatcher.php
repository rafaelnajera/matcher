<?php

/*
 * The MIT License
 *
 * Copyright 2017 Rafael Nájera <rafael@najera.ca>.
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
 * Matching of one or more Pattern objects in parallel
 *
 * @author Rafael Nájera <rafael@najera.ca>
 */
class ParallelMatcher
{
    
    /**
     * An array of Pattern objects
     *
     * @var Matcher[]
     */
    private $matchers;
 
    /**
     * Info about matched patterns
     *
     * @var array
     */
    public $matched;
    
    /**
     * Constructor
     *
     * @param array $patternArray
     */
    public function __construct(array $patternArray = [])
    {
        $this->matchers = [];
        foreach ($patternArray as $pattern) {
            $this->matchers[] = new Matcher($pattern);
        }
        $this->matched = [];
    }
    
    public function reset()
    {
        $this->resetMatchers();
        $this->matched = [];
    }
    
    private function resetMatchers()
    {
        foreach ($this->matchers as $matcher) {
            $matcher->reset();
        }
    }
    
    
    /**
     * Attempts to match an input token with
     * the patterns
     *
     * @param any|Token $input
     */
    public function match($input)
    {
       
        $noMatch = true;
        foreach ($this->matchers as $matcher) {
            if ($matcher->noMatch()) {
                continue;
            }
            if ($matcher->match($input)) {
                $noMatch = false;
            }
            if ($matcher->matchFound()) {
                $this->matched[] = $matcher->matched;
                $this->resetMatchers();
                break;
            }
        }
        if ($noMatch) {
            return false;
        }
        return true;
    }
    
    /**
     * Matches an array of tokens against the configured patterns
     *
     * Returns true if there were no mismatches in the input
     * array. The total number of matches can be found with
     * numMatches()
     *
     * @param array $tokens
     * @param boolean $reset
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
    
    public function numMatches()
    {
        return count($this->matched);
    }
}
