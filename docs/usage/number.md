# Number

## Purpose

`Number` is the core numeric type in Gauss. It is designed to provide a predictable decimal-based arithmetic layer for calculations that should not silently drift because of native PHP float behavior.

## Core types

The main type is:

- `Gauss\Number\Number`

The implementation stores values as normalized string-backed decimals and performs arithmetic using BCMath-aware helpers.

## Basic usage

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Number\Number;

$a = Number::of('10.5');
$b = Number::of('2.5');

$result = $a
    ->mul($b)
    ->add(Number::of('1'));

echo $result->value();
```

This produces:

```text
27.125
```

## Common operations

```php
$sum = $a->add($b);
$diff = $a->sub($b);
$product = $a->mul($b);
$ratio = $a->div($b);
$comparison = $a->compare($b);
```

The library keeps the arithmetic intent explicit and readable.

## Precision notes

`Number` is precise with respect to its represented decimal values. The implementation is designed around decimal normalization and controlled rounding, which makes it suitable for exact arithmetic when values are given as strings or integer literals.

This is not a claim that every numerical method in the entire library is exact. Some distribution and approximation functions may still rely on operational limits or numerical approximation.

## Composition

`Number` is the base building block used by:

- `Probability`
- `Vector`
- `Matrix`
- distribution parameters
- algebraic expressions

## Related modules

- [probability.md](probability.md)
- [distribution.md](distribution.md)
- [linear.md](linear.md)
- [algebra.md](algebra.md)
