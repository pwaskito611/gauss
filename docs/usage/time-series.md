# Time Series

## Purpose

The time series module models ordered observations and supports basic transformations such as lagging, differencing, and autocorrelation. It is built around immutable `TimeSeries` values and `Observation` objects.

## Core types

- `Gauss\TimeSeries\TimeSeries`
- `Gauss\TimeSeries\Observation`
- `Gauss\TimeSeries\Transform\Lag`
- `Gauss\TimeSeries\Transform\Difference`
- `Gauss\TimeSeries\Smoothing\MovingAverage`
- `Gauss\TimeSeries\Model\AR`
- `Gauss\TimeSeries\Model\MA`

## Creating a series

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\TimeSeries\TimeSeries;

$series = TimeSeries::of([10, 12, 15, 14, 18, 20]);

$first = $series->first();
$last = $series->last();

echo $first->value()->value() . PHP_EOL;
echo $last->value()->value() . PHP_EOL;
```

`TimeSeries::of()` accepts a list of values and normalizes them into indexed `Observation` records. The first and last observations are available through `first()` and `last()`.

## Transformations

```php
<?php

use Gauss\TimeSeries\TimeSeries;
use Gauss\TimeSeries\Transform\Difference;
use Gauss\TimeSeries\Transform\Lag;

$series = TimeSeries::of([10, 12, 15, 14, 18]);

$lagged = Lag::apply($series, 1);
$differenced = Difference::apply($series, 1);

 echo $lagged->first()->value()->value() . PHP_EOL;
 echo $differenced->first()->value()->value() . PHP_EOL;
```

Use `lag()` or the static transform helpers to align values or compute differences across time steps.

## Statistical checks

```php
<?php

use Gauss\TimeSeries\TimeSeries;

$series = TimeSeries::of([1, 2, 3, 4, 5]);

$variance = $series->variance();
$acf = $series->acf(1);

 echo $variance->value() . PHP_EOL;
 echo $acf->value() . PHP_EOL;
```

The series object includes helpers like `count()`, `values()`, `map()`, `slice()`, `variance()`, `acf()`, and `pacf()`. These are useful when preparing a sequence for forecasting or model fitting.

## Related modules

- [statistics.md](statistics.md)
- [linear.md](linear.md)
- [number.md](number.md)
