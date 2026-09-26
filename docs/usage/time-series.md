# Time Series

## Purpose

The time series module models sequences of observations over time using `Number` values and composable series operations.

## Core types

- `Gauss\TimeSeries\TimeSeries`
- `Gauss\TimeSeries\Observation`

## Basic usage

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\TimeSeries\TimeSeries;

$series = TimeSeries::of([1, 2, 3, 4, 5]);

$first = $series->first()->value();
$last = $series->last()->value();

echo $first->value() . ' -> ' . $last->value();
```

## Common operations

- `count()`
- `values()`
- `map()`
- `slice()`
- `lag()`
- `difference()`
- `variance()`
- `acf()`

## Composition

Time series logic works with `Number` values and can be combined with statistics and linear algebra when modelling temporal relationships.

## Related modules

- [statistics.md](statistics.md)
- [linear.md](linear.md)
- [number.md](number.md)
