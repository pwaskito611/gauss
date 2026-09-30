# Distribution

## Purpose

The distribution module defines common discrete and continuous distributions for probability calculations. Each distribution is parameterized by `Number` values and exposes a probability function together with its expectation and variance.

## Core types

- `Gauss\Distribution\Poisson`
- `Gauss\Distribution\Normal`
- `Gauss\Distribution\Bernoulli`
- `Gauss\Distribution\Binomial`
- `Gauss\Distribution\Uniform`
- `Gauss\Distribution\Exponential`

## Poisson distribution

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Distribution\Poisson;

$poisson = Poisson::of('3');

$pmf = $poisson->pmf(2);
$cdf = $poisson->cdf(2);

echo $pmf->value()->value() . PHP_EOL;
echo $cdf->value()->value() . PHP_EOL;
```

`Poisson::of()` accepts a positive rate. `pmf()` returns the probability mass at a specific count, and `cdf()` returns the cumulative probability up to that point.

## Normal distribution

```php
<?php

use Gauss\Distribution\Normal;

$normal = Normal::of(0, 1);

$pdf = $normal->pdf(0);
$cdf = $normal->cdf(0);

echo $pdf->value() . PHP_EOL;
echo $cdf->value()->value() . PHP_EOL;
```

The normal distribution is parameterized by its mean and standard deviation. The standard deviation must be strictly positive.

## Expected value and variance

```php
<?php

use Gauss\Distribution\Poisson;

$poisson = Poisson::of('2.5');

echo $poisson->expectation()->value() . PHP_EOL; // 2.5
echo $poisson->variance()->value() . PHP_EOL;    // 2.5
```

The distribution interface exposes `expectation()` and `variance()` as general properties of the model.

## Related modules

- [probability.md](probability.md)
- [statistics.md](statistics.md)
- [number.md](number.md)
