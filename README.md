# Matcher

Matcher implements a regexp-like matching system that can be used with user-devised
tokens

## Installation 
It is recommended that you use [Composer](https://getcomposer.org/) to install Matcher.

Matcher requires PHP 7.0 or above to run and PHPUnit 5.0 for development.

## Usage

Matcher allows you to match a sequence of tokens against a pattern specified 
in a regexp-like manner. 

The following sets up a RegExpPath object to match  '^ab(cd)*e'

```php
$path = new RegExpPath();
        
$s  = StateArray::addConcatConditions([], ['a', 'b']);
$s  = StateArray::addStatesZeroOrMore($s,
             StateArray::addConcatConditions([], ['c', 'd']));
$s  = StateArray::addStates($s,
             StateArray::addConcatConditions([], ['e']));
$path->pushStates($s);
```

The sequence to be matched can be given to the path one by one

```php
$r = $path->match('a');
$r = $path->match('b');
...
```
Here $r will be 'false' if the path does not match the input. $r will be true
if the sequence is still "alive", that is, if the sequence still matches the 
pattern in $path. 

The sequence to be matched can also be given as an array: 

```php
$r = $path->matchArray(['a', 'b', 'c']);

```
In either case, when there's a full match the method $path->foundMatch() will return true, 
and $path->matched will contain the actual sequence matched. 

A callback can be provided that will be called when a full match occurs. The 
callback function is called with $path->matched as its only argument and
its output will overwrite $path->matched.

The following code, for example, will cause $path->matched to be 'abc' instead
of an array:
```php
$path = new RegExpPath();
$path->pushStates(StateArray::concatConditions(['a', 'b', 'c']));
        
$path->setCallback( 
    function ($m) {
        return implode($m);
    }
);

$path->matchArray(['a', 'b', 'c', 'e']);
```




