# Linear Algebra

## Purpose

The linear module is the foundation for vectors, matrices, and basic solving workflows in Gauss.

## Core types

- `Gauss\Linear\Vector`
- `Gauss\Linear\Matrix`
- `Gauss\Linear\LinearSystem`

## Basic usage

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Linear\Vector;
use Gauss\Number\Number;

$v = Vector::of(1, 2, 3);
$w = Vector::of(3, 2, 1);

$sum = $v->add($w);
$dot = $v->dot($w);

print_r(array_map(static fn ($value) => $value->value(), $sum->values()));
echo $dot->value();
```

A vector stores `Number` objects and supports arithmetic, dot products, norms, normalization, and geometric operations.

## Matrix usage

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Linear\Matrix;

$matrix = Matrix::of([
    [1, 2],
    [3, 4],
]);

$vector = \Gauss\Linear\Vector::of(5, 6);
$result = $matrix->multiplyVector($vector);

print_r(array_map(static fn ($value) => $value->value(), $result->values()));
```

## Common operations

- `Vector::add()` and `Vector::sub()`
- `Vector::dot()` and `Vector::norm()`
- `Vector::normalize()`
- `Matrix::add()`, `Matrix::sub()`, `Matrix::transpose()`
- `Matrix::multiplyVector()`
- `Matrix::identity()` and `Matrix::zero()`

## Composition

Linear primitives are frequently used together with distribution and probability primitives to build more complex models. A vector can hold rates, weights, or parameters while a matrix can encode transitions or coefficients.

## Related modules

- [number.md](number.md)
- [probability.md](probability.md)
- [distribution.md](distribution.md)
- [statistics.md](statistics.md)
