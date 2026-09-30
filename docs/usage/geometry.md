# Geometry

## Purpose

The geometry module contains simple 2D/ND objects for points, segments, lines, and circles. Like the rest of Gauss, it works on top of `Number` and `Vector`, so distance and intersection calculations stay consistent with the library's exact arithmetic model.

## Core types

- `Gauss\Geometry\Point`
- `Gauss\Geometry\Line`
- `Gauss\Geometry\Segment`
- `Gauss\Geometry\Circle`

## Points and distances

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Geometry\Point;

$origin = Point::of(0, 0);
$target = Point::of(3, 4);

$distance = $origin->distanceTo($target);
$vector = $origin->vectorTo($target);

 echo $distance->value() . PHP_EOL; // 5
 echo $vector->get(0)->value() . PHP_EOL; // 3
```

`Point::of()` accepts coordinates and stores them as a `Vector`. `distanceTo()` returns the Euclidean distance, while `vectorTo()` returns the displacement vector from one point to another.

## Segments and lines

```php
<?php

use Gauss\Geometry\Line;
use Gauss\Geometry\Point;
use Gauss\Geometry\Segment;

$start = Point::of(0, 0);
$end = Point::of(4, 0);
$segment = Segment::between($start, $end);

$length = $segment->length();
$midpoint = $segment->midpoint();

$line = Line::through($start, $segment->direction());
$contains = $line->contains(Point::of(2, 0));

echo $length->value() . PHP_EOL; // 4
 echo $midpoint->coordinates()->get(0)->value() . PHP_EOL; // 2
 echo ($contains ? 'true' : 'false') . PHP_EOL;
```

Use `Segment::between()` for a finite straight segment and `Line::through()` to define an infinite line from a base point and a direction vector.

## Circles

```php
<?php

use Gauss\Geometry\Circle;
use Gauss\Geometry\Point;

$circle = Circle::of(Point::of(0, 0), '5');

$area = $circle->area();
$circumference = $circle->circumference();
$inside = $circle->contains(Point::of(3, 4));

echo $area->value() . PHP_EOL;
echo $circumference->value() . PHP_EOL;
 echo ($inside ? 'true' : 'false') . PHP_EOL;
```

Circles are defined by a center point and a non-negative radius. `area()` and `circumference()` rely on `Number::pi()` and respect the library's decimal arithmetic.

## Related modules

- [linear.md](linear.md)
- [number.md](number.md)
- [algebra.md](algebra.md)
