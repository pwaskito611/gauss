# Numerical

## Overview

The `Gauss\Numerical` namespace provides reusable routines for numerical differentiation, numerical integration, interpolation, and root finding. All of the algorithms in this module operate on the library's `Gauss\Number\Number` type and accept numeric inputs through the same conversion path used elsewhere in Gauss.

This documentation is implementation-aware: it describes the public API and validation rules that are actually enforced by the current source code.

Numeric methods accept a trailing `bool $precision = true`. The default keeps
BCMath; passing `false` selects float arithmetic for algorithm-owned inputs and
intermediates. Callback arguments and results are rebound at the algorithm
boundary, but calculations performed independently inside a user callback
remain outside Gauss's control. See [Precision modes](precision.md).

## Core routines

The numerical module currently exposes these 10 implementations:

### Differentiation

- `Gauss\Numerical\Differentiation\ForwardDifference`
- `Gauss\Numerical\Differentiation\BackwardDifference`
- `Gauss\Numerical\Differentiation\CentralDifference`

### Integration

- `Gauss\Numerical\Integration\TrapezoidalRule`
- `Gauss\Numerical\Integration\SimpsonRule`

### Interpolation

- `Gauss\Numerical\Interpolation\LinearInterpolation`
- `Gauss\Numerical\Interpolation\LagrangeInterpolation`

### Root finding

- `Gauss\Numerical\Root\Bisection`
- `Gauss\Numerical\Root\NewtonRaphson`
- `Gauss\Numerical\Root\Secant`

These classes are designed as static utility APIs. Each class is `final` and defines a private constructor, so the intended usage style is through static methods such as `Bisection::solve(...)` and `ForwardDifference::approximate(...)`. They are not meant to be instantiated with `new`.

## API style

All numerical routines in this module are accessed through static methods:

```php
$root = Bisection::solve(...);
$derivative = CentralDifference::approximate(...);
$integral = SimpsonRule::integrate(...);
$value = LinearInterpolation::interpolate(...);
```

This static pattern is the public API shape implemented by the source code.

## Common callable contract

The numerical routines accept a callable shaped like this:

```php
callable(Number): Number
```

Example:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Number\Number;

$fn = static fn (Number $x): Number => $x->pow(2)->sub(Number::of(2));
```

The callable is evaluated with `Gauss\Number\Number` arguments, and the result must also be a `Number`.

## Numeric input and output

The primary numeric type for these routines is `Gauss\Number\Number`.

Most public methods accept any of the numerically compatible input forms used by `Number::of()`:

- `int`
- `float`
- `string`
- `Gauss\Number\Number`

The methods then normalize those values via `Number::of()`. The final return value is always a `Gauss\Number\Number`.

`Number::of()` validates numeric strings while normalizing inputs. A string that
does not represent a supported numeric value raises `InvalidArgumentException`.

## Parameters and validation

The numerical API validates arguments immediately and throws exceptions when the method contract is violated.

Common validation rules in the implementation:

- step size must not be zero
- tolerance must be positive
- max iteration count must be positive
- subdivisions must be positive
- Simpson's rule requires an even number of subdivisions
- integration interval must satisfy `lower <= upper`
- root-finding intervals must satisfy `lower < upper`
- secant requires distinct initial guesses
- linear interpolation requires distinct x-values (`x0 != x1`)

## Exceptions

Two exception classes appear in the numerical modules:

### InvalidArgumentException

Used for invalid parameter values and invalid method preconditions.

Examples include:

- step equals zero
- tolerance <= 0
- maxIterations <= 0
- subdivisions <= 0
- Simpson subdivisions is odd
- lower > upper
- `x0 == x1` for linear interpolation
- duplicate x-values in Lagrange interpolation
- not enough interpolation points
- bisection interval lacking a sign change

### LogicException

Used when the iterative method cannot continue because of a runtime condition in the algorithm itself.

Examples include:

- Newton-Raphson derivative is zero at the current iterate
- Secant denominator is zero
- Newton-Raphson, bisection, or secant method fails to converge within the configured iteration limit

## Differentiation

### Forward Difference

The forward difference approximation is:

```text
f'(x) ~= (f(x + h) - f(x)) / h
```

The implementation is:

```php
public static function approximate(
    callable $function,
    int|float|string|Number $x,
    int|float|string|Number $step,
): Number
```

Parameters:

- `function`: a callable that accepts a `Number` and returns a `Number`
- `x`: evaluation point
- `step`: finite difference step size

Validation:

- `step` must not be zero, otherwise `InvalidArgumentException` is thrown

Return value:

- a `Gauss\Number\Number` estimate of the derivative

Example:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Number\Number;
use Gauss\Numerical\Differentiation\ForwardDifference;

$derivative = ForwardDifference::approximate(
    static fn (Number $x): Number => $x->pow(2),
    2,
    '0.001',
);

echo $derivative->value() . PHP_EOL; // approximately 4
```

### Backward Difference

The backward difference approximation is:

```text
f'(x) ~= (f(x) - f(x - h)) / h
```

This is implemented by:

```php
public static function approximate(
    callable $function,
    int|float|string|Number $x,
    int|float|string|Number $step,
): Number
```

Parameters:

- `function`: callable mapping `Number` to `Number`
- `x`: evaluation point
- `step`: finite difference step size

Validation:

- `step` must not be zero

Return value:

- a `Gauss\Number\Number` estimate of the derivative

Example:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Number\Number;
use Gauss\Numerical\Differentiation\BackwardDifference;

$derivative = BackwardDifference::approximate(
    static fn (Number $x): Number => $x->pow(2),
    2,
    '0.001',
);

echo $derivative->value() . PHP_EOL; // approximately 4
```

### Central Difference

The central difference approximation is:

```text
f'(x) ~= (f(x + h) - f(x - h)) / (2h)
```

The implementation signature is:

```php
public static function approximate(
    callable $function,
    int|float|string|Number $x,
    int|float|string|Number $step,
): Number
```

Parameters:

- `function`: callable to differentiate
- `x`: point of differentiation
- `step`: step size used on both sides of `x`

Validation:

- `step` must not be zero

Return value:

- a `Gauss\Number\Number` approximate derivative

Example:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Number\Number;
use Gauss\Numerical\Differentiation\CentralDifference;

$derivative = CentralDifference::approximate(
    static fn (Number $x): Number => $x->pow(2),
    2,
    '0.001',
);

echo $derivative->value() . PHP_EOL; // approximately 4
```

## Numerical integration

### Trapezoidal Rule

The method approximates:

```text
∫_a^b f(x) dx ~= h * [ (f(a) + f(b)) / 2 + Σ_{i=1}^{n-1} f(a + i h) ]
```

with:

```text
h = (b - a) / n
```

The implementation is:

```php
public static function integrate(
    callable $function,
    int|float|string|Number $lower,
    int|float|string|Number $upper,
    int $subdivisions = 100,
): Number
```

Parameters:

- `function`: integrand, a callable from `Number` to `Number`
- `lower`: lower bound `a`
- `upper`: upper bound `b`
- `subdivisions`: number of subintervals; default is `100`

Validation:

- `subdivisions` must be positive
- integration interval must satisfy `lower <= upper`
- if `lower == upper`, the method returns `0`

Examples:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Number\Number;
use Gauss\Numerical\Integration\TrapezoidalRule;

$integral = TrapezoidalRule::integrate(
    static fn (Number $x): Number => $x->pow(2),
    0,
    1,
    100,
);

echo $integral->value() . PHP_EOL;
```

### Simpson's Rule

The method approximates:

```text
∫_a^b f(x) dx ~= h/3 * [f(x_0) + f(x_n) + 4 Σ_{odd} f(x_i) + 2 Σ_{even} f(x_i)]
```

where:

```text
h = (b - a) / n
```

The implementation is:

```php
public static function integrate(
    callable $function,
    int|float|string|Number $lower,
    int|float|string|Number $upper,
    int $subdivisions = 100,
): Number
```

Parameters:

- `function`: integrand callable
- `lower`: lower bound `a`
- `upper`: upper bound `b`
- `subdivisions`: number of equal subintervals; default is `100`

Validation:

- `subdivisions` must be positive
- `subdivisions` must be even
- `lower <= upper` is required
- if `lower == upper`, the result is `0`

The even-subdivision requirement is enforced explicitly in the implementation because Simpson's rule is defined for an even partition count.

Example:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Number\Number;
use Gauss\Numerical\Integration\SimpsonRule;

$integral = SimpsonRule::integrate(
    static fn (Number $x): Number => $x->pow(2),
    0,
    1,
    100,
);

echo $integral->value() . PHP_EOL;
```

## Interpolation

### Linear Interpolation

For two points `(x0, y0)` and `(x1, y1)`, the implementation evaluates:

```text
y = y0 + (x - x0) * (y1 - y0) / (x1 - x0)
```

The method is:

```php
public static function interpolate(
    int|float|string|Number $x0,
    int|float|string|Number $y0,
    int|float|string|Number $x1,
    int|float|string|Number $y1,
    int|float|string|Number $x,
): Number
```

Parameters:

- `x0`, `y0`: first known point
- `x1`, `y1`: second known point
- `x`: target x-value

Validation:

- `x0` and `x1` must be distinct; otherwise `InvalidArgumentException` is thrown

Return value:

- interpolated `Number` at the target x-value

Example:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Numerical\Interpolation\LinearInterpolation;

$value = LinearInterpolation::interpolate(0, 0, 2, 4, 1);

echo $value->value() . PHP_EOL; // 2
```

### Lagrange Interpolation

Lagrange interpolation constructs the polynomial through a set of points:

```text
P(x) = Σ_{i=0}^{n} y_i L_i(x)
```

with:

```text
L_i(x) = Π_{j != i} (x - x_j) / (x_i - x_j)
```

The implementation is:

```php
public static function interpolate(
    array $points,
    int|float|string|Number $x,
): Number
```

Parameters:

- `points`: an array of coordinate pairs in the form `[x, y]`
  - each point is represented as a pair like `[x, y]`
  - the structural type is `array<array{0: int|float|string|Number, 1: int|float|string|Number}>`
- `x`: target x-value

Validation:

- at least two points are required
- duplicate x-values are rejected

Return value:

- interpolated `Number` at the target x-value

Example:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Numerical\Interpolation\LagrangeInterpolation;

$points = [
    [0, 0],
    [1, 1],
    [2, 4],
];

$value = LagrangeInterpolation::interpolate($points, 1.5);

echo $value->value() . PHP_EOL; // 2.25
```

## Root finding

### Bisection

The bisection method repeatedly bisects an interval and keeps the half where the sign change remains.

The midpoint is:

```text
c = (a + b) / 2
```

The method is:

```php
public static function solve(
    callable $function,
    int|float|string|Number $lower,
    int|float|string|Number $upper,
    int|float|string|Number $tolerance = '0.000001',
    int $maxIterations = 1000,
): Number
```

Parameters:

- `function`: callable returning `Number` values
- `lower`: left endpoint `a`
- `upper`: right endpoint `b`
- `tolerance`: convergence tolerance; default is `'0.000001'`
- `maxIterations`: maximum iteration count; default is `1000`

Validation:

- `lower < upper` is required
- `tolerance` must be positive
- `maxIterations` must be positive
- the implementation checks that the function changes sign across the interval; otherwise it throws `InvalidArgumentException`
- if the function is exactly zero at the lower or upper endpoint, the corresponding endpoint is returned immediately

If the method fails to converge within the iteration limit, it throws `LogicException`.

Example:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Number\Number;
use Gauss\Numerical\Root\Bisection;

$root = Bisection::solve(
    static fn (Number $x): Number => $x->pow(2)->sub(Number::of(2)),
    1,
    2,
    '0.000001',
    1000,
);

echo $root->value() . PHP_EOL; // approximately 1.41421356...
```

### Newton-Raphson

The Newton-Raphson update is:

```text
x_{n+1} = x_n - f(x_n) / f'(x_n)
```

The method is:

```php
public static function solve(
    callable $function,
    callable $derivative,
    int|float|string|Number $initialGuess,
    int|float|string|Number $tolerance = '0.000001',
    int $maxIterations = 100,
): Number
```

Parameters:

- `function`: objective function
- `derivative`: derivative function
- `initialGuess`: starting estimate `x_0`
- `tolerance`: convergence threshold; default `'0.000001'`
- `maxIterations`: maximum iteration count; default `100`

Validation:

- `tolerance` must be positive
- `maxIterations` must be positive

Runtime behavior:

- if `f(x)` is zero at the current iterate, it returns that value immediately
- if the derivative is zero, the method throws `LogicException`
- if the step becomes smaller than the tolerance, it returns the next iterate
- if convergence is not achieved before the iteration cap, it throws `LogicException`

Example:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Number\Number;
use Gauss\Numerical\Root\NewtonRaphson;

$root = NewtonRaphson::solve(
    static fn (Number $x): Number => $x->pow(2)->sub(Number::of(2)),
    static fn (Number $x): Number => Number::of(2)->mul($x),
    1.5,
    '0.000001',
    100,
);

echo $root->value() . PHP_EOL; // approximately 1.41421356...
```

### Secant Method

The secant method uses the update:

```text
x_{n+1} = x_n - f(x_n) * (x_n - x_{n-1}) / (f(x_n) - f(x_{n-1}))
```

The method is:

```php
public static function solve(
    callable $function,
    int|float|string|Number $firstGuess,
    int|float|string|Number $secondGuess,
    int|float|string|Number $tolerance = '0.000001',
    int $maxIterations = 100,
): Number
```

Parameters:

- `function`: objective function
- `firstGuess`: first initial value `x_0`
- `secondGuess`: second initial value `x_1`
- `tolerance`: convergence threshold; default `'0.000001'`
- `maxIterations`: maximum iteration count; default `100`

Validation:

- the two initial guesses must be different
- tolerance must be positive
- maxIterations must be positive

Runtime behavior:

- if `f(x0)` or `f(x1)` is zero, it returns that root immediately
- if the secant denominator is zero, it throws `LogicException`
- if the next iterate is within the tolerance, it returns the new value
- if convergence fails, it also throws `LogicException`

Example:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Number\Number;
use Gauss\Numerical\Root\Secant;

$root = Secant::solve(
    static fn (Number $x): Number => $x->pow(2)->sub(Number::of(2)),
    1,
    2,
    '0.000001',
    100,
);

echo $root->value() . PHP_EOL; // approximately 1.41421356...
```

## Edge cases and implementation behavior

The following edge cases are relevant to the current implementations:

### Differentiation

- `step = 0` is rejected by all three difference methods

### Integration

- `subdivisions <= 0` is rejected
- `lower > upper` is rejected
- `lower == upper` returns `0` for both trapezoidal and Simpson integration
- Simpson's rule requires an even number of subdivisions

### Interpolation

- `LinearInterpolation` rejects `x0 == x1`
- `LagrangeInterpolation` requires at least two points
- duplicate x-values are rejected

### Root finding

- `Bisection` requires a valid bracket with sign change
- `NewtonRaphson` rejects a zero derivative at the current iterate
- `Secant` rejects identical initial guesses and zero denominator values
- all three root solvers reject non-positive tolerance values and non-positive iteration caps

## Related modules

- [number.md](number.md)
- [distribution.md](distribution.md)
- [linear.md](linear.md)
