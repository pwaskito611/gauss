# Optimization

## Overview

The optimization module contains search routines for minimizing or maximizing scalar objectives over a bounded interval or vector space. Output is wrapped in `Gauss\Optimization\OptimizationResult`, which contains the best point found, the objective value at that point, the iteration count, and a convergence flag.

The module provides:

- bounded one-dimensional search with `GoldenSectionSearch`;
- multidimensional gradient-based search with `GradientDescent`;
- coordinate-wise bounded search with `CoordinateDescent`;
- derivative-free simplex search with `NelderMead`;
- box constraints with `BoxConstraint`.

Values are converted through `Gauss\Number\Number`. One-dimensional objectives receive a `Number`; multidimensional objectives receive a `Gauss\Linear\Vector`. Calculations inherit `Number`'s decimal precision and rounding behavior. Search routines that divide or take square roots are deterministic under those rules, but are not universally exact.

## Core types

| Type | Purpose |
| --- | --- |
| `Gauss\Optimization\OptimizationResult` | Immutable result containing the best point, objective value, iteration count, and convergence flag. |
| `Gauss\Optimization\OneDimensional\GoldenSectionSearch` | Bounded one-dimensional minimization and maximization. |
| `Gauss\Optimization\Multidimensional\GradientDescent` | Finite-difference gradient descent or ascent with line search. |
| `Gauss\Optimization\Multidimensional\CoordinateDescent` | Coordinate-wise bounded minimization. |
| `Gauss\Optimization\DerivativeFree\NelderMead` | Derivative-free Nelder-Mead simplex minimization or maximization. |
| `Gauss\Optimization\Constraint\BoxConstraint` | Coordinate-wise lower and upper bounds for multidimensional routines. |

## Result model

Every search routine returns an `OptimizationResult`.

```php
$result->point();      // Number|Vector
$result->value();      // Number
$result->iterations(); // int
$result->converged();  // bool
```

`point()` returns a `Number` for one-dimensional routines and a `Vector` for multidimensional routines.

`value()` returns the objective value in the original sign. For maximization routines, the internal search minimizes the negated objective and restores the original value before returning.

`iterations()` reports the number of iterations executed before stopping.

`converged()` is `true` when the routine met its stopping criterion. It is `false` when the routine stopped because `maxIterations` was reached, unless the stopping criterion was also satisfied at that point.

## Input model

Objective callables have different signatures depending on the routine:

```php
// One-dimensional
static fn (Number $x): Number => ...;

// Multidimensional
static fn (Vector $x): Number => ...;
```

Numeric parameters such as bounds, tolerances, learning rates, and finite-difference steps may be integers, floats, numeric strings, or `Number` values. Prefer strings when preserving a decimal literal matters, since an input float may already contain binary floating-point error.

Objectives must return a scalar value convertible through `Number::of()`. Multidimensional routines use `Gauss\Linear\Vector` for points and, when supplied, `BoxConstraint` for bounds.

## One-dimensional optimization

### `GoldenSectionSearch`

`GoldenSectionSearch::minimize()` and `maximize()` accept a callable objective and a search interval.

```php
public static function minimize(
    callable $objective,
    int|float|string|Number $lower,
    int|float|string|Number $upper,
    int|float|string|Number $tolerance = '0.000001',
    int $maxIterations = 1000,
): OptimizationResult;

public static function maximize(
    callable $objective,
    int|float|string|Number $lower,
    int|float|string|Number $upper,
    int|float|string|Number $tolerance = '0.000001',
    int $maxIterations = 1000,
): OptimizationResult;
```

The search interval must satisfy `lower < upper`; otherwise `InvalidArgumentException` is thrown. The tolerance must be positive, and `maxIterations` must be positive.

`minimize()` searches for the smallest objective value. `maximize()` searches for the largest objective value. Internally, maximization negates the objective, minimizes the negated value, and restores the original sign in the returned `OptimizationResult`.

The algorithm stops when the interval width is less than or equal to the tolerance. If the iteration limit is reached first, `converged()` indicates whether the width criterion was satisfied at that point.

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Number\Number;
use Gauss\Optimization\OneDimensional\GoldenSectionSearch;

$result = GoldenSectionSearch::minimize(
    static fn (Number $x): Number => $x->pow(2)->add(Number::of(1)),
    -10,
    10,
    '0.000001',
    1000,
);

echo $result->point()->value() . PHP_EOL;
echo $result->value()->value() . PHP_EOL;
```

## Multidimensional optimization

### `GradientDescent`

`GradientDescent::minimize()` and `maximize()` perform finite-difference gradient descent or ascent with a one-dimensional line search along the step direction.

```php
public static function minimize(
    callable $objective,
    Vector $initial,
    int|float|string|Number $learningRate,
    int|float|string|Number $tolerance = '0.000001',
    int $maxIterations = 1000,
    int|float|string|Number $gradientStep = '0.000001',
    int|float|string|Number|null $lineSearchLowerBound = 0,
    int|float|string|Number|null $lineSearchUpperBound = null,
    ?BoxConstraint $bounds = null,
): OptimizationResult;

public static function maximize(
    callable $objective,
    Vector $initial,
    int|float|string|Number $learningRate,
    int|float|string|Number $tolerance = '0.000001',
    int $maxIterations = 1000,
    int|float|string|Number $gradientStep = '0.000001',
    int|float|string|Number|null $lineSearchLowerBound = 0,
    int|float|string|Number|null $lineSearchUpperBound = null,
    ?BoxConstraint $bounds = null,
): OptimizationResult;
```

The learning rate must be positive. It also acts as the default line-search scale: when `lineSearchUpperBound` is not supplied, it defaults to ten times the learning rate. The default `lineSearchLowerBound` is zero. An explicit line-search bound overrides the corresponding default.

The gradient is approximated by finite differences using `gradientStep`, which must be positive. When `bounds` is supplied, finite differences use one-sided steps near the boundary, and the line-search interval is clipped to the box. A generated candidate outside the bounds throws `InvalidArgumentException`.

At each iteration:

1. compute the finite-difference gradient;
2. stop successfully if the gradient norm is less than or equal to `tolerance`;
3. choose the gradient direction, negated for minimization and positive for maximization;
4. minimize the one-dimensional line objective over the feasible interval using `GoldenSectionSearch`;
5. stop successfully if the step distance is less than or equal to `tolerance`.

If the iteration limit is reached first, `converged()` is `false`.

```php
<?php

use Gauss\Linear\Vector;
use Gauss\Optimization\Multidimensional\GradientDescent;

$result = GradientDescent::minimize(
    static fn (Vector $x): \Gauss\Number\Number => $x->get(0)->pow(2)->add($x->get(1)->pow(2)),
    Vector::of(3, 3),
    '0.1',
    '0.000001',
    500,
);

echo $result->point()->get(0)->value() . PHP_EOL;
```

### `CoordinateDescent`

`CoordinateDescent::minimize()` minimizes an objective by optimizing one coordinate at a time while holding the others fixed.

```php
public static function minimize(
    callable $objective,
    Vector $initial,
    int|float|string|Number $tolerance = '0.000001',
    int $maxIterations = 1000,
    ?BoxConstraint $bounds = null,
): OptimizationResult;
```

Explicit bounds are required. `CoordinateDescent` throws `InvalidArgumentException` when `bounds` is `null`. The bounds dimension must match the initial point, and the initial point must already be inside the bounds. The tolerance and iteration limit must be positive.

For each coordinate, the routine builds a one-dimensional objective by replacing that coordinate while keeping the other coordinates fixed. It then calls `GoldenSectionSearch::minimize()` over that coordinate's lower and upper bound. Coordinates whose lower and upper bounds are equal are skipped.

After a full coordinate sweep, the routine checks two stopping conditions:

- the distance between the previous and current vectors is less than or equal to `tolerance`;
- the absolute change in objective value is less than or equal to `tolerance`.

If either condition is met, the result is returned with `converged()` set to `true`. If the iteration limit is reached first, `converged()` is `false`.

```php
<?php

use Gauss\Linear\Vector;
use Gauss\Optimization\Constraint\BoxConstraint;
use Gauss\Optimization\Multidimensional\CoordinateDescent;

$bounds = BoxConstraint::from(
    Vector::of(-5, -5),
    Vector::of(5, 5),
);

$result = CoordinateDescent::minimize(
    static fn (Vector $x): \Gauss\Number\Number => $x->get(0)->pow(2)->add($x->get(1)->pow(2)),
    Vector::of(3, 3),
    '0.000001',
    500,
    $bounds,
);

echo $result->point()->get(0)->value() . PHP_EOL;
```

### `NelderMead`

`NelderMead::minimize()` and `maximize()` implement the derivative-free Nelder-Mead simplex method.

```php
public static function minimize(
    callable $objective,
    array $simplex,
    int|float|string|Number $tolerance = '0.000001',
    int $maxIterations = 500,
    ?BoxConstraint $bounds = null,
): OptimizationResult;

public static function maximize(
    callable $objective,
    array $simplex,
    int|float|string|Number $tolerance = '0.000001',
    int $maxIterations = 500,
    ?BoxConstraint $bounds = null,
): OptimizationResult;
```

The simplex must contain exactly `dimension + 1` distinct `Vector` points, and every point must have the same dimension. The dimension must be at least one. When bounds are supplied, the bounds dimension must match the simplex dimension, and every initial simplex point must already be inside the bounds.

The method performs reflection, expansion, contraction, and shrink operations. When bounds are supplied, generated candidates are clamped coordinate-wise into the box before evaluation.

The method stops successfully when the maximum coordinate spread between the best simplex vertex and the other vertices is less than or equal to `tolerance`. If the iteration limit is reached first, `converged()` is `false`.

```php
<?php

use Gauss\Linear\Vector;
use Gauss\Optimization\DerivativeFree\NelderMead;

$result = NelderMead::minimize(
    static fn (Vector $x): \Gauss\Number\Number => $x->get(0)->pow(2)->add($x->get(1)->pow(2)),
    [
        Vector::of(3, 3),
        Vector::of(4, 3),
        Vector::of(3, 4),
    ],
    '0.000001',
    500,
);

echo $result->point()->get(0)->value() . PHP_EOL;
```

## Constraints

### `BoxConstraint`

`BoxConstraint` represents coordinate-wise lower and upper bounds.

```php
public static function from(Vector $lower, Vector $upper): self;

public function lower(): Vector;
public function upper(): Vector;
public function dimension(): int;
public function contains(Vector $point): bool;
```

The lower and upper vectors must have the same dimension. Each lower bound must be less than or equal to the matching upper bound; otherwise `InvalidArgumentException` is thrown.

`contains()` returns `false` when the point dimension does not match the constraint dimension. Otherwise, it checks each coordinate inclusively:

```php
$lower[$i] <= $point[$i] <= $upper[$i]
```

```php
<?php

use Gauss\Linear\Vector;
use Gauss\Optimization\Constraint\BoxConstraint;

$bounds = BoxConstraint::from(
    Vector::of(-1, -1),
    Vector::of(1, 1),
);

$bounds->contains(Vector::of(0, 0)); // true
$bounds->contains(Vector::of(2, 0)); // false
```

## Precision model

Optimization uses `Number` for numeric conversion, arithmetic, and comparison. The exactness and rounding behavior therefore depend on the operations a routine uses. Inputs supplied as floats may already have lost decimal precision before conversion.

| Routine / operation | Precision behavior |
| --- | --- |
| `GoldenSectionSearch` | Uses decimal addition, subtraction, multiplication, and comparison. Objective evaluation may introduce its own rounding. |
| `GradientDescent` | Finite differences use division; gradient norm uses square root; line search delegates to `GoldenSectionSearch`. |
| `CoordinateDescent` | Uses `GoldenSectionSearch` per coordinate and `Vector::distance()` for convergence; distance may use square root. |
| `NelderMead` | Centroid uses division by simplex count; reflection, expansion, contraction, and shrink use decimal arithmetic. |
| `BoxConstraint` | Uses exact `Number` comparisons for bounds checks. |
| `OptimizationResult` | Stores the computed `Number` or `Vector` values without additional rounding. |

## Exceptions and edge cases

| API | Exception | Condition |
| --- | --- | --- |
| `GoldenSectionSearch::minimize()` / `maximize()` | `InvalidArgumentException` | `lower >= upper` |
| `GoldenSectionSearch::minimize()` / `maximize()` | `InvalidArgumentException` | `tolerance <= 0` |
| `GoldenSectionSearch::minimize()` / `maximize()` | `InvalidArgumentException` | `maxIterations <= 0` |
| `GradientDescent::minimize()` / `maximize()` | `InvalidArgumentException` | Learning rate is not positive |
| `GradientDescent::minimize()` / `maximize()` | `InvalidArgumentException` | Tolerance is not positive |
| `GradientDescent::minimize()` / `maximize()` | `InvalidArgumentException` | Gradient step is not positive |
| `GradientDescent::minimize()` / `maximize()` | `InvalidArgumentException` | Line-search lower bound is not smaller than upper bound |
| `GradientDescent::minimize()` / `maximize()` | `InvalidArgumentException` | Bounds dimension does not match initial point |
| `GradientDescent::minimize()` / `maximize()` | `InvalidArgumentException` | Initial point is outside supplied bounds |
| `GradientDescent::minimize()` / `maximize()` | `InvalidArgumentException` | `maxIterations <= 0` |
| `GradientDescent::minimize()` / `maximize()` | `InvalidArgumentException` | Line-search interval contains no point inside the bounds |
| `GradientDescent::minimize()` / `maximize()` | `InvalidArgumentException` | Line objective generates a candidate outside the bounds |
| `CoordinateDescent::minimize()` | `InvalidArgumentException` | Bounds are not supplied |
| `CoordinateDescent::minimize()` | `InvalidArgumentException` | Bounds dimension does not match initial point |
| `CoordinateDescent::minimize()` | `InvalidArgumentException` | Initial point is outside supplied bounds |
| `CoordinateDescent::minimize()` | `InvalidArgumentException` | Tolerance is not positive |
| `CoordinateDescent::minimize()` | `InvalidArgumentException` | `maxIterations <= 0` |
| `NelderMead::minimize()` / `maximize()` | `InvalidArgumentException` | Simplex is empty |
| `NelderMead::minimize()` / `maximize()` | `InvalidArgumentException` | Simplex contains a non-`Vector` value |
| `NelderMead::minimize()` / `maximize()` | `InvalidArgumentException` | Simplex points have inconsistent dimensions |
| `NelderMead::minimize()` / `maximize()` | `InvalidArgumentException` | Simplex dimension is less than one |
| `NelderMead::minimize()` / `maximize()` | `InvalidArgumentException` | Simplex size is not `dimension + 1` |
| `NelderMead::minimize()` / `maximize()` | `InvalidArgumentException` | Bounds dimension does not match simplex dimension |
| `NelderMead::minimize()` / `maximize()` | `InvalidArgumentException` | A simplex point is outside supplied bounds |
| `NelderMead::minimize()` / `maximize()` | `InvalidArgumentException` | Simplex vertices are not distinct |
| `NelderMead::minimize()` / `maximize()` | `InvalidArgumentException` | Tolerance is not positive |
| `NelderMead::minimize()` / `maximize()` | `InvalidArgumentException` | `maxIterations <= 0` |
| `BoxConstraint::from()` | `InvalidArgumentException` | Lower and upper bounds have different dimensions |
| `BoxConstraint::from()` | `InvalidArgumentException` | A lower bound is greater than its matching upper bound |
| Objective conversion | `InvalidArgumentException` | An objective returns a value that is not a valid finite `Number` input |

## Complete example

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Linear\Vector;
use Gauss\Number\Number;
use Gauss\Optimization\Constraint\BoxConstraint;
use Gauss\Optimization\DerivativeFree\NelderMead;
use Gauss\Optimization\Multidimensional\CoordinateDescent;
use Gauss\Optimization\Multidimensional\GradientDescent;
use Gauss\Optimization\OneDimensional\GoldenSectionSearch;

$oneDimensional = GoldenSectionSearch::minimize(
    static fn (Number $x): Number => $x->pow(2)->add(Number::of(1)),
    -10,
    10,
);

$gradient = GradientDescent::minimize(
    static fn (Vector $x): Number => $x->get(0)->pow(2)->add($x->get(1)->pow(2)),
    Vector::of(3, 3),
    '0.1',
    '0.000001',
    500,
);

$bounds = BoxConstraint::from(
    Vector::of(-5, -5),
    Vector::of(5, 5),
);

$coordinate = CoordinateDescent::minimize(
    static fn (Vector $x): Number => $x->get(0)->pow(2)->add($x->get(1)->pow(2)),
    Vector::of(3, 3),
    '0.000001',
    500,
    $bounds,
);

$nelderMead = NelderMead::minimize(
    static fn (Vector $x): Number => $x->get(0)->pow(2)->add($x->get(1)->pow(2)),
    [
        Vector::of(3, 3),
        Vector::of(4, 3),
        Vector::of(3, 4),
    ],
    '0.000001',
    500,
);
```

## Related modules

- [numerical.md](numerical.md)
- [linear.md](linear.md)
- [statistics.md](statistics.md)