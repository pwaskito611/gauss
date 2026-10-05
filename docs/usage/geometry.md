# Geometry

## Overview

The `Gauss\Geometry` module provides coordinate-based primitives for points,
segments, lines, and circles. `Point`, `Segment`, and most `Line` operations
use N-dimensional vectors. `Line::intersectionWith()` is restricted to 2D.
`Circle` represents a circle conceptually in 2D, although its current
implementation stores an N-dimensional center and does not enforce a
two-coordinate center.

Geometry values are built on `Gauss\Linear\Vector` and
`Gauss\Number\Number`. Addition and multiplication operate on stored decimal
values; operations involving division or square roots follow `Number`'s
rounded precision behavior. Geometry calculations are therefore not
universally exact.

## Dimensionality

| Type or operation | Dimensionality |
| --- | --- |
| `Point` | N-dimensional |
| `Segment` | N-dimensional, provided its points have matching dimensions |
| `Line` construction, containment, projection, and distance | N-dimensional, provided point and direction dimensions match |
| `Line::intersectionWith()` | 2D lines only |
| `Circle` | Conceptually 2D; the implementation does not validate center dimensionality |

Operations that combine points or vectors of incompatible dimensions inherit
`Vector`'s `InvalidArgumentException` behavior.

## Point

### Construction and coordinates

`Point::of()` accepts one or more integer, float, string, or `Number`
coordinates and stores them in a `Vector`. Calling it without coordinates
throws `InvalidArgumentException`.

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Geometry\Point;

$point = Point::of(1, 2);
$coordinates = $point->coordinates(); // Vector(1, 2)
```

`coordinates()` returns the point's coordinate `Vector`.

### Distance and displacement

`distanceTo($other)` returns the Euclidean distance as a `Number`.
`vectorTo($other)` returns the displacement from this point to the other
point, calculated as `other - this`. Both require matching dimensions.

```php
$origin = Point::of(0, 0);
$target = Point::of(3, 4);

$origin->distanceTo($target)->value(); // "5"
$origin->vectorTo($target)->get(0)->value(); // "3"
```

Distance uses a vector norm and `Number::sqrt()`, so its result is rounded
half-up to 50 decimal places where needed.

### Midpoint

`midpoint($other)` returns the point halfway between the two points. It
requires matching dimensions and divides by two, so the result follows
`Number::div()`'s rounded precision behavior. Different point dimensions
throw `InvalidArgumentException` through the underlying vector operation.
In a characteristic-2 numeric domain, division by two is invalid and the
method throws `LogicException`.

```php
$p = Point::of(0, 0);
$q = Point::of(4, 0);

$midpoint = $p->midpoint($q); // (2, 0)
```

The current `Number` implementation is a decimal domain in which two is
non-zero, so the characteristic-2 guard does not normally trigger.

### Translation and equality

`translate($vector)` returns a point with the vector added to its
coordinates. The vector dimension must match.

`equals($other)` compares coordinate values. It returns `false` for points of
different dimensions rather than throwing.

```php
$p = Point::of(0, 0);

$translated = $p->translate(\Gauss\Linear\Vector::of(1, 1));
$same = $p->equals(Point::of(0, 0)); // true
$differentDimension = $p->equals(Point::of(0, 0, 0)); // false
```

## Line

A line consists of a base `Point` and a non-zero direction `Vector`.
Construction does not separately validate matching dimensions; operations
that combine incompatible dimensions throw `InvalidArgumentException`.

### Construction and inspection

`Line::through($point, $direction)` constructs a line. A zero direction vector
is rejected with `InvalidArgumentException`.

```php
use Gauss\Geometry\Line;
use Gauss\Linear\Vector;

$line = Line::through(
    Point::of(0, 0),
    Vector::of(1, 0)
);

$basePoint = $line->point();
$direction = $line->direction();
```

`point()` and `direction()` return the stored base point and direction vector.

### Containment

`contains($point)` checks whether the displacement from the line's base point
to the given point is parallel to the direction. This uses
`Vector::isParallelTo()`. Dimensions must be compatible.

```php
$line->contains(Point::of(0, 0)); // true: the base point lies on the line
$line->contains(Point::of(3, 0)); // true
$line->contains(Point::of(3, 1)); // false
```

The base point is always considered contained: its displacement is the zero
vector, which `Vector::isParallelTo()` treats as parallel to the line
direction.

### Projection

`project($point)` returns the orthogonal projection onto the line, using a
dot product and scalar division. The result follows `Number::div()`'s rounded
precision behavior.

```php
$line = Line::through(Point::of(0, 0), Vector::of(1, 0));
$projection = $line->project(Point::of(3, 4)); // (3, 0)
```

If the direction's self-dot-product is zero, projection is undefined and
`LogicException` is thrown. A non-zero direction over the current decimal
`Number` domain has a non-zero self-dot-product.

### Distance

`distanceTo($point)` returns the distance from the point to its projection on
the line. It uses `project()` followed by a vector norm, so its result follows
the 50-decimal half-up square-root behavior of `Number`.

```php
$line->distanceTo(Point::of(3, 4))->value(); // "4"
```

### Intersection

`intersectionWith($other)` returns the unique intersection point of two
2D lines:

```php
$horizontal = Line::through(Point::of(0, 0), Vector::of(1, 0));
$vertical = Line::through(Point::of(0, 0), Vector::of(0, 1));

$intersection = $horizontal->intersectionWith($vertical); // (0, 0)
```

Both direction vectors must be 2D; otherwise `InvalidArgumentException` is
thrown. Parallel or coincident lines have no unique intersection and cause
`LogicException`. The calculation uses division and follows
`Number::div()`'s precision behavior.

## Segment

A segment is defined by its two endpoint points and supports N-dimensional
coordinates when those points have matching dimensions.

### Construction, endpoints, and direction

`Segment::between($start, $end)` constructs a segment.

```php
use Gauss\Geometry\Segment;

$segment = Segment::between(Point::of(0, 0), Point::of(4, 0));

$start = $segment->start();
$end = $segment->end();
$direction = $segment->direction(); // end - start: (4, 0)
```

The accessors return the stored endpoints; `direction()` returns the vector
from start to end.

### Length and midpoint

`length()` returns the Euclidean distance between endpoints and uses a vector
norm, so it follows `Number::sqrt()`'s rounded 50-decimal behavior.
`midpoint()` delegates to `Point::midpoint()` and follows its division
precision and characteristic-2 behavior.

```php
$segment->length()->value(); // "4"
$segment->midpoint();        // (2, 0)
```

### Containment

`contains($point)` first checks whether the point lies along the segment's
direction, then computes its parameter along the segment. The parameter must
be in the inclusive interval `[0, 1]`, so both endpoints are contained.
Parallelism and the parameter calculation use vector operations and division.

```php
$segment->contains(Point::of(2, 0)); // true
$segment->contains(Point::of(5, 0)); // false
$segment->contains(Point::of(2, 1)); // false
```

If start and end are equal, the segment is degenerate: the same point is
contained and any other point is not. A point not parallel to the segment
direction returns `false`. If the direction self-dot-product is zero after
the non-degenerate branch, `LogicException` is thrown; this guard is not
normally reachable with a non-zero vector in the current decimal `Number`
domain.

## Circle

### Construction, center, and radius

`Circle::of($center, $radius)` accepts a `Point` and an integer, float,
numeric string, or `Number` radius. Negative radii throw
`InvalidArgumentException`; zero radius is allowed.

```php
use Gauss\Geometry\Circle;

$circle = Circle::of(Point::of(0, 0), 5);

$center = $circle->center();
$radius = $circle->radius(); // Number(5)
```

The intended geometric model is 2D, but the implementation stores any
`Point` dimension and does not enforce a 2D center.

### Area and circumference

The formulas are `area = πr²` and `circumference = 2πr`.
`area()` and `circumference()` use `Number::pi()`, which is a fixed
50-decimal approximation. The results are deterministic decimal
approximations, not exact values of the corresponding real-number formulas.

```php
$circle->area();
$circle->circumference();
```

### Containment

`contains($point)` includes the boundary: a point is contained when its
distance from the center is less than or equal to the radius. It uses
`Point::distanceTo()` and thus inherits the 50-decimal rounded square-root
behavior. The point and center must have matching dimensions.

```php
$circle->contains(Point::of(5, 0)); // true: on circumference
$circle->contains(Point::of(3, 4)); // true: also on circumference
```

## Precision model

Geometry delegates numeric operations to `Number`. Exact decimal arithmetic
applies to addition, subtraction, and multiplication of stored values.
Division uses rounded decimal arithmetic; square roots use half-up rounding
to 50 decimal places. `Number::pi()` is a fixed approximation with 50 decimal
places.

| Geometry operation | Numeric dependency | Precision behavior |
| --- | --- | --- |
| `Point::distanceTo()` | Vector norm → `sqrt()` | Half-up rounding to 50 decimal places |
| `Point::midpoint()` | `div()` by two | `Number` division rounding |
| `Point::vectorTo()` / `translate()` | Vector subtraction / addition | Exact decimal operations on stored values |
| `Line::contains()` | `Vector::isParallelTo()` | Exact in 3D cross-product path; other dimensions can use rounded division |
| `Line::project()` | Dot products and `div()` | `Number` division rounding |
| `Line::distanceTo()` | Projection and vector norm | Division rounding plus square-root rounding |
| `Line::intersectionWith()` | 2D determinant and `div()` | `Number` division rounding |
| `Segment::length()` | Vector norm → `sqrt()` | Half-up rounding to 50 decimal places |
| `Segment::midpoint()` | Point midpoint | `Number` division rounding |
| `Segment::contains()` | Parallel check and parameter `div()` | May use rounded division |
| `Circle::area()` / `circumference()` | `Number::pi()` and multiplication | Based on the 50-decimal π approximation |
| `Circle::contains()` | Point distance → `sqrt()` | Half-up rounding to 50 decimal places |

## Dependencies

Geometry types compose the existing `Vector` and `Number` abstractions:

```text
Point   ── coordinates ──> Vector
Line    ── base point ───> Point
        └─ direction ────> Vector
Segment ── endpoints ────> Point
        └─ direction ────> Vector
Circle  ── center ───────> Point
        └─ radius ───────> Number
```

Important behavior dependencies include:

- `Line::contains()` uses `Vector::isParallelTo()`.
- `Line::project()` uses vector dot products, scaling, and point translation.
- `Line::distanceTo()` uses `project()` and a vector norm.
- `Segment::contains()` uses vector parallelism and dot products.
- `Circle::contains()` uses `Point::distanceTo()`.

These relationships explain how `Number`'s precision behavior propagates
through higher-level geometry operations.

## Edge cases and exceptions

| API | Exception | Condition |
| --- | --- | --- |
| `Point::of()` | `InvalidArgumentException` | No coordinates supplied |
| `Point::distanceTo()`, `vectorTo()`, `midpoint()`, `translate()` | `InvalidArgumentException` | Coordinate/vector dimensions do not match |
| `Point::midpoint()` | `LogicException` | Division by two is undefined in characteristic 2 |
| `Line::through()` | `InvalidArgumentException` | Direction is the zero vector |
| `Line::contains()`, `project()`, `distanceTo()` | `InvalidArgumentException` | Point and line dimensions do not match |
| `Line::project()` | `LogicException` | Direction self-dot-product is zero |
| `Line::intersectionWith()` | `InvalidArgumentException` | Either direction is not 2D |
| `Line::intersectionWith()` | `LogicException` | Lines are parallel or coincident, so no unique intersection exists |
| `Segment::length()`, `direction()`, `midpoint()` | `InvalidArgumentException` | Endpoint dimensions do not match |
| `Segment::contains()` | `InvalidArgumentException` | Point and segment dimensions do not match |
| `Segment::contains()` | `LogicException` | Direction self-dot-product is zero after non-degenerate branch |
| `Circle::of()` | `InvalidArgumentException` | Radius is negative |
| `Circle::contains()` | `InvalidArgumentException` | Point and center dimensions do not match |

`Point::equals()` is the exception to the usual dimension-mismatch rule: it
returns `false` when point dimensions differ.

## Related modules

- [linear.md](linear.md)
- [number.md](number.md)
- [algebra.md](algebra.md)
