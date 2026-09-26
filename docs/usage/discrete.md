# Discrete

## Purpose

The discrete module captures combinatorial and discrete mathematical structures that fit naturally within Gauss’s core numeric approach.

## Core concepts

Discrete functionality in Gauss is represented through reusable mathematical objects rather than by a monolithic framework API.

## Basic usage

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Algebra\Polynomial;
use Gauss\Number\Number;

$polynomial = Polynomial::of([
    0 => Number::of(1),
    1 => Number::of(2),
    2 => Number::of(1),
]);

echo $polynomial->degree();
```

This demonstrates the discrete-algebraic flavor of the library: small reusable structures that can be composed into more elaborate calculations.

## Related modules

- [algebra.md](algebra.md)
- [number.md](number.md)
- [probability.md](probability.md)
