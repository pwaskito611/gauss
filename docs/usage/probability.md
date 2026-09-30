# Probability

## Purpose

The probability module is built around a constrained value type: every `Probability` is guaranteed to live in the closed interval $[0, 1]$. The layer also includes sample spaces, events, and probability measures for building simple probabilistic models.

## Core types

- `Gauss\Probability\Probability`
- `Gauss\Probability\SampleSpace`
- `Gauss\Probability\Event`
- `Gauss\Probability\ProbabilityMeasure`
- `Gauss\Probability\RandomVariable`

## Basic probability values

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Probability\Probability;

$p = Probability::of('0.7');
$q = Probability::of('0.2');

$result = $p->multiply($q);
$complement = $p->complement();

echo $result->value()->value() . PHP_EOL; // 0.14
echo $complement->value()->value() . PHP_EOL; // 0.3
```

The constructor validates the interval and rejects values smaller than zero or larger than one.

## Sample spaces and events

```php
<?php

use Gauss\Probability\Event;
use Gauss\Probability\SampleSpace;

$space = SampleSpace::of('heads', 'tails');
$heads = Event::of($space, 'heads');
$tails = Event::of($space, 'tails');

$union = $heads->union($tails);
$probabilityOfUnion = $union->outcomes();

echo count($probabilityOfUnion) . PHP_EOL; // 2
```

`Event::of()` constructs an event within a sample space. `union()`, `intersection()`, `difference()`, and `complement()` are all available for set-style reasoning.

## Probability measures

```php
<?php

use Gauss\Probability\Event;
use Gauss\Probability\ProbabilityMeasure;
use Gauss\Probability\SampleSpace;

$space = SampleSpace::of('A', 'B', 'C');
$measure = ProbabilityMeasure::of($space, ['0.2', '0.5', '0.3']);
$event = Event::of($space, 'A', 'C');

$probability = $measure->probabilityOf($event);

echo $probability->value()->value() . PHP_EOL; // 0.5
```

A probability measure assigns each outcome a valid weight that sums to exactly 1. It can then answer questions such as `probabilityOf($event)` or `conditional($a, $b)`.

## Related modules

- [distribution.md](distribution.md)
- [statistics.md](statistics.md)
- [number.md](number.md)
