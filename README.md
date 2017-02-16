# Matcher

Matcher implements a regexp-like matching system that can be used with user-devised
tokens

## Installation 
It is recommended that you use [Composer](https://getcomposer.org/) to install Matcher.

Matcher requires PHP 7.0 or above to run and PHPUnit 5.0 for development.

## Usage

Matcher allows you to match a sequence of tokens against a pattern specified 
in a regexp-like manner. 

The main class is Pattern, which stands for a regexp-like pattern that can be matched.

The following sets up a Pattern object to match  `'^ab(cd)*e'`:

```php
        $p = (new Pattern())->withTokenSeries(['a', 'b'])
                ->withAddedPatternZeroOrMore((new Pattern())->withTokenSeries(['c', 'd']))
                ->withTokenSeries(['e']);
```
The tokens used to set up the pattern can be of any type. Matching is done 
by strict comparison with the input tokens.  Tokens can also be objects
that implement the `Token` interface, in which case the token's `matches($someInput)` 
method will be called. The input in this case can be anything as long as the
`matches()` method knows how to determine a match.

Once set up, input tokens can be fed to the pattern one by one with the `match` method:

```php
$r = $p->match('a');
$r = $p->match('b');
...
```
Here `$r` will be false if the input does not match the pattern. `$r` will be true
if the sequence is still "alive", that is, if the sequence still matches the 
pattern in `$p`.  When a full match is found the `matchFound()` method returns
true:
```php
$m = $p->matchFound();
```

The public variable `$p->matched` at this point will contain the actual sequence
of matched tokens or, if tokens implement the `Token` interface, whatever the
`matched($someInput)` method returns. This array of matched token information 
can be manipulated during the matching process with callbacks as explained
below.

The `reset()` method, resets the internal state of the pattern as if no
token had been fed to it.

```php
$p->reset();
```
Input tokens can also be given in an array, in which case the pattern is
reset before starting:

```php
$r = $p->matchArray(['a', 'b', 'c']);
```

NOTE: there might be cases in which an empty token or EOF token would need
to be provided so that a complete match can be determined, `matchArray` does
not provide an end token itself.

### Callbacks

A callback can be provided that will be called when a full match occurs. The 
callback function is called with `$p->matched` as its only argument and
its output will overwrite `$p->matched`.

The following code, for example, will cause $p->matched to be `'abc'` instead
of the array `['a', 'b', 'c']`:
```php
$p = (new Pattern())->withTokenSeries(['a', 'b', 'c'])
   ->withCallback( 
    function ($m) {
        return implode($m);
    }
);

$p->matchArray(['a', 'b', 'c', 'e']);

$p->matchFound();  // true
$p->matched;  // 'abc'
```

Callbacks are retained in their proper places when patterns are added. 

