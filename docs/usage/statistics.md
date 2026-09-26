# Statistics

## Purpose

The statistics module provides summary statistics and related numerical helpers over `Number` values and vectors.

## Core types

- `Gauss\Statistics\Statistics`

## Basic usage

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Linear\Vector;
use Gauss\Statistics\Statistics;

$data = Vector::of(1, 2, 3, 4, 5);

$mean = Statistics::mean($data);
$variance = Statistics::populationVariance($data);

echo $mean->value() . PHP_EOL;
echo $variance->value() . PHP_EOL;
```

## Common operations

- `count()`
- `sum()`
- `mean()`
- `median()`
- `mode()`
- `min()`
- `max()`
- `range()`
- `populationVariance()`
- `sampleVariance()`
- `populationStandardDeviation()`
- `coefficientOfVariation()`

## Composition

Statistics is often built on top of `Number` and `Vector`, and then used together with probability or optimization workflows.

## Example

```php
$values = Vector::of(10, 12, 14, 16);
$sd = Statistics::sampleStandardDeviation($values);
```

## Related modules

- [number.md](number.md)
- [probability.md](probability.md)
- [linear.md](linear.md)
