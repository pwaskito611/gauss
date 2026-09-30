# Numerical

## Purpose

The numerical module contains reusable root-finding, interpolation, differentiation, and integration routines that operate on Gauss's `Number` values. These are practical algorithms for solving small numerical problems without leaving the library's type system.

## Core routines

- `Gauss\Numerical\Root\Bisection`
- `Gauss\Numerical\Root\NewtonRaphson`
- `Gauss\Numerical\Root\Secant`
- `Gauss\Numerical\Interpolation\LinearInterpolation`
- `Gauss\Numerical\Integration\TrapezoidalRule`
- `Gauss\Numerical\Integration\SimpsonRule`
- `Gauss\Numerical\Differentiation\ForwardDifference`
- `Gauss\Numerical\Differentiation\CentralDifference`

## Root finding with bisection

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

`Bisection::solve()` expects a callable returning a `Number`, a bracketing interval, and a tolerance. The function must change sign across the interval.

## Numerical integration

```php
<?php

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

Integration routines work directly with callables and `Number` inputs. The same pattern applies to Simpson's rule and finite-difference approximations.

## Related modules

- [number.md](number.md)
- [linear.md](linear.md)
- [optimization.md](optimization.md)
