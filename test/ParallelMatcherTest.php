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

use PHPUnit\Framework\TestCase;

/**
 * Description of ParallelMatcherTest
 *
 * @author Rafael Nájera <rafael@najera.ca>
 */
class ParallelMatcherTest extends TestCase
{
    
    public function testSimple()
    {
        $pmatcher = new ParallelMatcher(
            [
                (new Pattern())->withTokenSeries(['(', 'a', ')']),
                (new Pattern())->withTokenSeries(['(', 'b', ')']),
                (new Pattern())->withTokenSeries(['(', 'c', ')'])
            ]
        );
        
        //print_r($pmatcher);
        $result = $pmatcher->matchArray(['(', 'c', ')', '(', 'c', ')']);
        
        $this->assertEquals(true, $result);
        $this->assertEquals(2, $pmatcher->numMatches());
        $this->assertEquals(['(', 'c', ')'], $pmatcher->matched[0]);
        $this->assertEquals(['(', 'c', ')'], $pmatcher->matched[1]);
        
        $result = $pmatcher->matchArray(['(', 'c', ')', '(', 'x', ')']);
        $this->assertEquals(false, $result);
        $this->assertEquals(1, $pmatcher->numMatches());
        $this->assertEquals(['(', 'c', ')'], $pmatcher->matched[0]);
    }
}
