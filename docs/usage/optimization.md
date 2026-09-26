# Optimization

## Purpose

The optimization module is for objective-driven search and parameter fitting using Gauss’s numeric primitives.

## Core types

- `Gauss\Optimization\OptimizationResult`
- optimization algorithms under `src/Optimization`

## Basic usage

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Linear\Vector;
use Gauss\Optimization\OptimizationResult;
use Gauss\Number\Number;

$result = new OptimizationResult(
    Vector::of(1, 2),
    Number::of('3.5'),
    12,
    true
);

echo $result->value()->value();
```

## Composition

Optimization operates naturally on `Vector`-based inputs and `Number`-based objective values. This makes it useful for custom numerical modelling tasks.

## Related modules

- [numerical.md](numerical.md)
- [linear.md](linear.md)
- [statistics.md](statistics.md)
