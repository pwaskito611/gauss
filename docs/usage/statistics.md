# Statistics

## Purpose

The statistics module gives Gauss a compact set of summary statistics over either plain PHP arrays or `Vector` objects. All calculations stay in the `Number` domain, which makes them deterministic and consistent with the rest of the library.

## Core type

- `Gauss\Statistics\Statistics`

## Basic usage

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Linear\Vector;
use Gauss\Statistics\Statistics;

$data = Vector::of(1, 2, 3, 4, 5);

$mean = Statistics::mean($data);
$median = Statistics::median($data);
$variance = Statistics::populationVariance($data);
$stdDev = Statistics::populationStandardDeviation($data);

echo $mean->value() . PHP_EOL; // 3
 echo $median->value() . PHP_EOL; // 3
echo $variance->value() . PHP_EOL; // 2
 echo $stdDev->value() . PHP_EOL; // 1.41421356237309504880168872420969807856967187537694
```

## Common calculations

```php
<?php

use Gauss\Linear\Vector;
use Gauss\Statistics\Statistics;

$values = Vector::of(10, 12, 14, 16, 18);

$sum = Statistics::sum($values);
$min = Statistics::min($values);
$max = Statistics::max($values);
$range = Statistics::range($values);
$sampleVariance = Statistics::sampleVariance($values);

 echo $sum->value() . PHP_EOL;
 echo $min->value() . PHP_EOL;
 echo $max->value() . PHP_EOL;
 echo $range->value() . PHP_EOL;
 echo $sampleVariance->value() . PHP_EOL;
```

The most useful helpers are `count()`, `sum()`, `mean()`, `median()`, `mode()`, `min()`, `max()`, `range()`, `populationVariance()`, `sampleVariance()`, and the standard deviation helpers.

## Quantiles and robust summaries

```php
<?php

use Gauss\Linear\Vector;
use Gauss\Statistics\Statistics;

$data = Vector::of(1, 2, 4, 7, 9, 10, 11, 15);

$q25 = Statistics::quantile($data, '0.25');
$q50 = Statistics::quantile($data, '0.5');
$q75 = Statistics::quantile($data, '0.75');

 echo $q25->value() . PHP_EOL;
 echo $q50->value() . PHP_EOL;
 echo $q75->value() . PHP_EOL;
```

The `quantile()` method expects a probability between 0 and 1 and returns the corresponding quantile from the ordered data.

## Related modules

- [number.md](number.md)
- [linear.md](linear.md)
- [probability.md](probability.md)
