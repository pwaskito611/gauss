# Algebra

## Purpose

The algebra module provides symbolic and structural primitives for building mathematical expressions over `Number` values.

## Core types

- `Gauss\Algebra\Polynomial`
- `Gauss\Algebra\Monomial`

## Basic usage

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Algebra\Polynomial;
use Gauss\Number\Number;

$poly = Polynomial::of([
    0 => Number::of(2),
    1 => Number::of(3),
    2 => Number::of(1),
]);

$sum = $poly->add(Polynomial::constant(Number::of(5)));

echo $sum->degree();
```

The `Polynomial` object stores sparse coefficients keyed by degree. This allows math to be represented in a composable way without losing numeric precision.

## Common operations

- `add()`
- `sub()`
- `mul()`
- `degree()`
- `coefficient()`
- `constantTerm()`
- `isZero()`

## Composition

The algebra layer is designed to work with `Number` as its arithmetic primitive. In a larger model, a polynomial can be used as a symbolic or numeric component inside a broader application-specific routine.

## Example

```php
$polyA = Polynomial::of([
    0 => Number::of(1),
    1 => Number::of(2),
    2 => Number::of(1),
]);

$polyB = Polynomial::of([
    0 => Number::of(3),
    1 => Number::of(4),
]);

$product = $polyA->mul($polyB);
```

## Related modules

- [number.md](number.md)
- [linear.md](linear.md)
- [numerical.md](numerical.md)
