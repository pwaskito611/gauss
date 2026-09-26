# Probability Model Example

This example combines a probability primitive with a simple distribution model.

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Distribution\Poisson;
use Gauss\Number\Number;
use Gauss\Probability\Probability;

$lambda = Number::of('2.5');
$distribution = Poisson::of($lambda);

$k = 3;
$pmf = $distribution->pmf($k);
$cdf = $distribution->cdf($k);

$probabilityOfThree = $pmf->value();
$probabilityAtMostThree = $cdf->value();

$rateProbability = Probability::of($probabilityOfThree);

echo 'P(X=3)=' . $rateProbability->value()->value() . PHP_EOL;
echo 'P(X<=3)=' . $probabilityAtMostThree->value() . PHP_EOL;
```

This demonstrates how a distribution can use `Number` parameters and produce a `Probability` result that remains inside the valid range.
