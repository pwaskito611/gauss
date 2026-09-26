# Probability

## Purpose

The probability module provides a validated numeric type for values in the closed interval `[0, 1]`.

## Core types

- `Gauss\Probability\Probability`
- `Gauss\Probability\ProbabilityMeasure`
- `Gauss\Probability\Event`

## Basic usage

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Probability\Probability;

$p = Probability::of('0.7');
$q = Probability::of('0.2');

$result = $p->multiply($q);

echo $result->value()->value();
```

The result is still a valid probability value:

```text
0.14
```

## Common operations

- `compare()`
- `complement()`
- `add()`
- `multiply()`
- `divide()`
- `isUnit()`
- `isZero()`

## Composition

Probability values are not isolated from the rest of the library. They can be used as inputs to distribution calculations, statistical measures, or larger model-building routines.

## Example

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Probability\Probability;

$heads = Probability::of('0.5');
$tails = $heads->complement();

echo $tails->value()->value();
```

This shows the basic semantics of the probability layer and how it preserves the invariant that probability values remain in the valid range.

## Related modules

- [distribution.md](distribution.md)
- [statistics.md](statistics.md)
- [composition.md](composition.md)
