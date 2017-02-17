# Matcher

Matcher implements a regexp-like matching system that can be used with user-devised
tokens

## Installation 
Install the latest version with

```bash
$ composer require rafaelnajera/matcher
```

## Usage

The main class is Matcher, which allows you to match a sequence 
of tokens against a pattern specified in a regexp-like manner. 

Matcher works on a Pattern object, which stands for a regexp-like pattern 
that can be matched.

The following sets up a Pattern object to match  `'^ab(cd)*e'`:

```php
$pattern = (new Pattern())->withTokenSeries(['a', 'b'])
           ->withAddedPatternZeroOrMore((new Pattern())->withTokenSeries(['c', 'd']))
           ->withTokenSeries(['e']);
```
The tokens used to set up the pattern can be of any type. Matching is done 
by strict comparison with the input tokens.  Tokens can also be objects
that implement the `Token` interface, in which case the token's `matches($someInput)` 
method will be called. The input in this case can be anything as long as the token's
`matches()` method knows how to determine a match.

Once set up, a Matcher object can be created and input tokens 
can be fed to the it one by one with the `match` method:

```php
$matcher = new Matcher($pattern);
$r = $matcher->match('a');
$r = $matcher->match('b');
...
```
Here `$r` will be false if the input does not match the pattern. `$r` will be true
if the sequence is still "alive", that is, if the sequence still matches the 
pattern in `$matcher`.  When a full match is found the `matchFound()` method returns
true:
```php
$m = $matcher->matchFound();
```

The public variable `$matcher->matched` at this point will contain the actual sequence
of matched tokens or, if tokens implement the `Token` interface, whatever the 
token's `matched($someInput)` method returns. This array of matched token information 
can be manipulated during the matching process with callbacks as explained
below.

The `reset()` method, resets the internal state of the pattern matcher as if no
token had been fed to it.

```php
$matcher->reset();
```
Input tokens can also be given in an array:

```php
$r = $matcher->matchArray(['a', 'b', 'c']);
```

By default this method resets the matcher before starting to match the elements
of the given array. An optional flag can be given to change this behaviour:

```php
$r = $matcher->matchArray(['a', 'b', 'c'], false);
```

### Callbacks

A callback can be provided that will be called when a full match occurs. The 
callback function is called with `$matcher->matched` as its only argument and
its output will overwrite `$matcher->matched`.

The following code, for example, will cause $matcher->matched to be `'abc'` instead
of the array `['a', 'b', 'c']`:
```php
$pattern = (new Pattern())->withTokenSeries(['a', 'b', 'c'])
   ->withCallback( 
    function ($m) {
        return implode($m);
    }
);

$matcher = new Matcher($pattern);
$matcher->matchArray(['a', 'b', 'c', 'e']);

$matcher->matchFound();  // true
$matcher->matched;  // 'abc'
```

Callbacks are retained in their proper places when patterns are added. This 
allows sub-patterns with specific callbacks to be created. For example:

```php
$subPattern = (new Pattern())->withTokenSeries(['c', 'd'])
     ->withCallback( function($m) { ... });

$pattern = (new Pattern())->withTokenSeries(['a', 'b'])
        ->withAddedPatternZeroOrMore($subPattern)
        ->withTokenSeries(['e']);

$matcher = new Matcher($pattern);
```

In this case, every time the 'cd' subpattern is matched, the callback will be
called. 

###End Token

The special constant `Token::EOF` stands for the end of input. It can be used
to set up patterns and also to signal the matcher the end of the input.
```php
$pattern = (new Pattern())->withTokenSeries(['a', 'b', Token::EOF]);
        
$matcher = new Matcher($pattern);
        
$matcher->matchArray(['a', 'b']);  // no match
$matcher->matchArray(['a', 'b', Token::EOF]); // match found!
```

