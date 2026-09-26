# Basic Statistical Computation

This example shows how Gauss can be used with `Number` and basic statistical constructions without requiring a larger application framework.

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Linear\Vector;
use Gauss\Number\Number;

$values = Vector::of(1, 2, 3, 4, 5);

$mean = Number::of(0);
foreach ($values->values() as $value) {
    $mean = $mean->add($value);
}
$mean = $mean->div($values->dimension());

$variance = Number::of(0);
foreach ($values->values() as $value) {
    $delta = $value->sub($mean);
    $variance = $variance->add($delta->mul($delta));
}
$variance = $variance->div($values->dimension());

$stdDev = $variance->sqrt();

echo 'mean=' . $mean->value() . PHP_EOL;
echo 'variance=' . $variance->value() . PHP_EOL;
echo 'stddev=' . $stdDev->value() . PHP_EOL;
```

The numbers are explicit and readable because the code mirrors the mathematics directly.
