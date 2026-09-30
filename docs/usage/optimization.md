# Optimization

## Purpose

The optimization module contains search routines for minimizing or maximizing scalar objectives over a bounded interval or vector space. Output is wrapped in `OptimizationResult`, which contains the best point found, the objective value, iteration count, and success flag.

## Core types

- `Gauss\Optimization\OptimizationResult`
- `Gauss\Optimization\OneDimensional\GoldenSectionSearch`
- `Gauss\Optimization\Multidimensional\GradientDescent`
- `Gauss\Optimization\Multidimensional\CoordinateDescent`
- `Gauss\Optimization\DerivativeFree\NelderMead`

## One-dimensional optimization

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Number\Number;
use Gauss\Optimization\OneDimensional\GoldenSectionSearch;

$result = GoldenSectionSearch::minimize(
    static fn (Number $x): Number => $x->pow(2)->add(Number::of(1)),
    -10,
    10,
    '0.000001',
    1000,
);

echo $result->point()->value() . PHP_EOL;
echo $result->value()->value() . PHP_EOL;
```

`GoldenSectionSearch::minimize()` and `maximize()` accept a callable objective and a search interval. They return an `OptimizationResult` with the best point and the corresponding objective value.

## Multidimensional optimization

```php
<?php

use Gauss\Linear\Vector;
use Gauss\Optimization\Multidimensional\GradientDescent;

$result = GradientDescent::minimize(
    static fn (Vector $x): \Gauss\Number\Number => $x->get(0)->pow(2)->add($x->get(1)->pow(2)),
    Vector::of(3, 3),
    '0.1',
    '0.000001',
    500,
);

echo $result->point()->get(0)->value() . PHP_EOL;
```

`GradientDescent` works with a vector-valued input and a callable that returns a scalar `Number`. The key parameters are the initial point, learning rate, tolerance, and iteration limit.

## Related modules

- [numerical.md](numerical.md)
- [linear.md](linear.md)
- [statistics.md](statistics.md)
