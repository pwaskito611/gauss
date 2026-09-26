# Distribution

## Purpose

The distribution module wraps probability calculations behind concrete distribution definitions such as Poisson or Bernoulli.

## Core types

- `Gauss\Distribution\Distribution`
- `Gauss\Distribution\Poisson`
- `Gauss\Distribution\Bernoulli`
- `Gauss\Distribution\Normal`

## Basic usage

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Distribution\Poisson;
use Gauss\Number\Number;

$lambda = Number::of('2.5');
$poisson = Poisson::of($lambda);

$pmf = $poisson->pmf(3);

echo $pmf->value()->value();
```

The probability mass function returns a `Probability` value that is constrained to the valid range.

## Common operations

- `pmf()`
- `cdf()`
- `expectation()`
- `variance()`

## Composition

Distributions are built from `Number` parameters and yield `Probability` values, which allows them to be combined with statistics, vector parameters, or custom model logic.

## Example

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Distribution\Poisson;
use Gauss\Number\Number;

$poisson = Poisson::of('3');

$probability = $poisson->cdf(2);
echo $probability->value()->value();
```

## Related modules

- [probability.md](probability.md)
- [statistics.md](statistics.md)
- [examples/probability-model.md](examples/probability-model.md)
