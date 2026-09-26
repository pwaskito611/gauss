# Geometry

## Purpose

The geometry module works with points, vectors, and spatial relationships using `Number` as the numeric foundation.

## Core types

- `Gauss\Geometry\Point`
- `Gauss\Geometry\Line`
- `Gauss\Geometry\Segment`

## Basic usage

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Geometry\Point;

$origin = Point::of(0, 0);
$target = Point::of(3, 4);

$distance = $origin->distanceTo($target);
echo $distance->value();
```

The geometric operations are expressed through the same numeric primitives used elsewhere in Gauss.

## Common operations

- `distanceTo()`
- `translate()`
- `midpoint()`
- `vectorTo()`

## Composition

Geometry inherits the same precise `Number` semantics as other modules. This makes it useful in combinations with linear algebra and model-building workflows.

## Related modules

- [linear.md](linear.md)
- [number.md](number.md)
