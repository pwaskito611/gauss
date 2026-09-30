# Linear Algebra

## Purpose

Gauss's linear module provides the building blocks for vector arithmetic, matrix operations, and structured linear workflows. These objects are based on `Number`, so they preserve exact decimal arithmetic while still supporting real-world matrix calculations.

## Core types

- `Gauss\Linear\Vector`
- `Gauss\Linear\Matrix`
- `Gauss\Linear\LinearSystem`
- `Gauss\Linear\LUDecomposition`

## Vector operations

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Linear\Vector;

$v = Vector::of(1, 2, 3);
$w = Vector::of(3, 2, 1);

$sum = $v->add($w);
$dot = $v->dot($w);
$norm = $v->norm();
$normalized = $v->normalize();

print_r(array_map(static fn ($value) => $value->value(), $sum->values()));
echo $dot->value() . PHP_EOL;
echo $norm->value() . PHP_EOL;
```

Common `Vector` operations include `add()`, `sub()`, `scale()`, `dot()`, `distance()`, `norm()`, and `normalize()`. These methods all return new vector objects rather than mutating the original value.

## Matrix construction

```php
<?php

use Gauss\Linear\Matrix;
use Gauss\Linear\Vector;

$matrix = Matrix::of([
    [1, 2],
    [3, 4],
]);

$zero = Matrix::zero(2, 2);
$identity = Matrix::identity(3);
$diagonal = Matrix::diagonal([2, 4, 6]);

$vector = Vector::of(5, 6);
$result = $matrix->multiplyVector($vector);

print_r(array_map(static fn ($value) => $value->value(), $result->values()));
```

`Matrix::of()` accepts a list of rows. `zero()`, `identity()`, and `diagonal()` are convenient factories for standard matrix shapes.

## Matrix algebra

```php
<?php

use Gauss\Linear\Matrix;

$matrix = Matrix::of([
    [2, 1],
    [1, 3],
]);

$transpose = $matrix->transpose();
$product = $matrix->multiply(Matrix::identity(2));
$determinant = $matrix->determinant();
$inverse = $matrix->inverse();

echo $determinant->value() . PHP_EOL;
```

`Matrix::transpose()` and `Matrix::multiply()` follow the standard linear-algebra conventions. `determinant()` and `inverse()` require a square matrix; `inverse()` will fail for singular matrices.

## Important matrix methods

- `shape()` returns `[rows, columns]`
- `rows()`, `columns()`, and `get()` inspect elements
- `row()` and `column()` extract a vector view
- `trace()`, `rank()`, and `diagonalValues()` are summary operations
- `isSquare()`, `isSymmetric()`, `isIdentity()`, and `isSingular()` check structural properties

## Related modules

- [number.md](number.md)
- [statistics.md](statistics.md)
- [distribution.md](distribution.md)
- [probability.md](probability.md)
