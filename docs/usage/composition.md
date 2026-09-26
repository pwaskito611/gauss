# Composition

Composition is one of the defining ideas in Gauss. The library does not ask a user to understand one giant domain object before writing useful code. Instead, it offers small primitives that can be composed into larger mathematical systems.

## Simple composition pattern

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Distribution\Poisson;
use Gauss\Number\Number;

$lambda = Number::of('2.5');
$distribution = Poisson::of($lambda);

$probability = $distribution->pmf(3);
echo $probability->value()->value();
```

This is a small example of a chain:

```text
Number
  ↓
Poisson distribution
  ↓
Probability result
```

## Larger model construction

A more complex model can combine several primitives:

```text
Number
  ↓
Probability
  ↓
Poisson
  ↓
Vector / Matrix
  ↓
Model
```

The important point is that Gauss permits the user to assemble a model from reusable, mathematically meaningful parts.

## Why this matters

The library is not built around one special domain object like an `Hsmm` class. Instead, the user can compose:

- numeric values,
- distribution laws,
- probability measures,
- vectors and matrices,
- and custom model logic.

This makes Gauss useful as a toolkit for custom mathematical modelling rather than merely a collection of static formulas.

## Rule of thumb

If a concept is mathematically meaningful, it can usually be represented as a small composable building block in Gauss. The complexity appears when those blocks are assembled into a larger model, not when they are first defined.
