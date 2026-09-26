# Numerical

## Purpose

The numerical module in Gauss contains methods and structures that operate on numeric and optimization workflows using the project’s `Number` primitive.

## Core concepts

The numerical layer relies on:

- exact arithmetic via `Number`
- vectors and matrices for computational state
- reusable solver and optimization patterns

## Basic usage

This module is primarily used together with linear and optimization primitives, rather than as a standalone self-contained framework.

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Linear\Vector;
use Gauss\Number\Number;

$point = Vector::of(2, 3);
$scale = Number::of('2');
$scaled = $point->scale($scale);

print_r(array_map(static fn($value) => $value->value(), $scaled->values()));
```

## Composition

The numerical layer is best understood as a collection of computational routines that work with Gauss’s value objects rather than as a separate numeric universe.

## Related modules

- [number.md](number.md)
- [linear.md](linear.md)
- [optimization.md](optimization.md)
