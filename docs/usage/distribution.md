# Distribution

## Overview

The `Gauss\Distribution` namespace contains a compact set of distribution models for probabilistic calculations. The module is intentionally API-first: each class exposes the same statistical contract and accepts numeric values in the common `int`, `float`, `string`, or `Gauss\Number\Number` forms used elsewhere in the library.

The public contract is defined by:

- `Gauss\Distribution\Distribution`
- `Gauss\Distribution\DiscreteDistribution`
- `Gauss\Distribution\ContinuousDistribution`
- `Gauss\Distribution\DiscreteIndexSupport`

The core types currently implemented are:

- `Gauss\Distribution\Bernoulli`
- `Gauss\Distribution\Binomial`
- `Gauss\Distribution\Exponential`
- `Gauss\Distribution\Geometric`
- `Gauss\Distribution\Normal`
- `Gauss\Distribution\Poisson`
- `Gauss\Distribution\Uniform`

## Core types

### Distribution

The root interface defines the shared statistical contract:

```php
interface Distribution
{
    public function expectation(): Number;
    public function variance(): Number;
}
```

This means every distribution exposes:

- `expectation()`: the mean `E[X]`
- `variance()`: the variance `Var(X)`

### DiscreteDistribution

```php
interface DiscreteDistribution extends Distribution
{
    public function pmf(int|float|string|Number $x): Probability;
    public function cdf(int|float|string|Number $x): Probability;
}
```

This contract is for discrete random variables and defines:

- `pmf(x)`: the point mass `P(X = x)`
- `cdf(x)`: the cumulative probability `P(X <= x)`

### ContinuousDistribution

```php
interface ContinuousDistribution extends Distribution
{
    public function pdf(int|float|string|Number $x): Number;
    public function cdf(int|float|string|Number $x): Probability;
}
```

This contract is for continuous random variables and defines:

- `pdf(x)`: the density `f_X(x)`
- `cdf(x)`: the cumulative probability `F_X(x) = P(X <= x)`

A PDF is not itself a probability. For a continuous variable, the probability of landing exactly at a single point is zero, but the density can be positive there.

### DiscreteIndexSupport

This trait provides floor-based support for discrete distributions that convert a real-valued input into an integer index.

Its floor semantics are mathematical floor, not integer cast:

```text
floorIndex(-0.1) = -1
floorIndex(-1.1) = -2
floorIndex(0.1) = 0
```

This matters because the implementation treats negative fractional values consistently with `floor`, not with truncation toward zero.

## Common API

All distribution classes in this package use the same pattern:

```php
$dist = Poisson::of('3');
$pmf = $dist->pmf(2);
$cdf = $dist->cdf(2);
$mean = $dist->expectation();
$variance = $dist->variance();
```

### PMF

For discrete distributions, `pmf(x)` returns `Gauss\Probability\Probability` and represents `P(X = x)`.

### PDF

For continuous distributions, `pdf(x)` returns `Gauss\Number\Number` and represents the density `f_X(x)` at that point.

### CDF

For both discrete and continuous distributions, `cdf(x)` returns `Gauss\Probability\Probability` and represents `P(X <= x)`.

### Expectation and variance

`expectation()` and `variance()` provide the first and second central moments of the modeled variable.

## Parameters and validation

The distributions validate their constructor arguments using actual implementation constraints.

- Bernoulli: `0 <= p <= 1`
- Binomial: `trials >= 0` and `0 <= p <= 1`
- Geometric: `0 < p <= 1`
- Poisson: `lambda > 0`
- Exponential: `rate > 0`
- Normal: `standardDeviation > 0`
- Uniform: `a < b`

Invalid values raise `InvalidArgumentException`.

## Input and output types

Distribution methods accept:

- `int`
- `float`
- `string`
- `Gauss\Number\Number`

Numeric strings are normalized through `Number::of()`, which allows precise decimal input without silently losing precision.

Outputs are:

- `Gauss\Probability\Probability` for PMF/CDF results
- `Gauss\Number\Number` for density and moment values

## Discrete distributions

### Bernoulli

A Bernoulli variable takes value 1 with probability `p` and 0 with probability `1 - p`.

Support: `{0, 1}`

PMF:

```text
P(X = 0) = 1 - p
P(X = 1) = p
```

Expectation:

```text
E[X] = p
```

Variance:

```text
Var(X) = p(1 - p)
```

Implementation notes:

- `p = 0` is valid and returns `P(X = 0) = 1`
- `p = 1` is valid and returns `P(X = 1) = 1`
- inputs outside `{0, 1}` return probability `0`

Example:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Distribution\Bernoulli;

$bernoulli = Bernoulli::of('0.75');

echo $bernoulli->pmf(1)->value()->value() . PHP_EOL; // 0.75

echo $bernoulli->cdf(0)->value()->value() . PHP_EOL; // 0.25
```

### Binomial

A Binomial variable counts the number of successes in `n` independent Bernoulli trials.

Support: `{0, 1, ..., n}`

PMF:

```text
P(X = k) = C(n, k) p^k (1 - p)^(n - k)
```

Expectation:

```text
E[X] = np
```

Variance:

```text
Var(X) = np(1 - p)
```

Implementation notes:

- `trials` must be `>= 0`
- `p` must satisfy `0 <= p <= 1`
- non-integer inputs return zero mass
- `n = 0` is allowed; the only valid support is `0`
- `p = 0` and `p = 1` are handled as degenerate cases

Example:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Distribution\Binomial;

$binomial = Binomial::of(5, '0.5');

echo $binomial->pmf(2)->value()->value() . PHP_EOL; // 0.3125

echo $binomial->cdf(2)->value()->value() . PHP_EOL; // 0.5
```

### Geometric

This implementation models the number of trials until the first success, inclusive.

Support: `{1, 2, 3, ...}`

PMF:

```text
P(X = k) = (1 - p)^(k - 1) p, for k >= 1
```

Expectation:

```text
E[X] = 1 / p
```

Variance:

```text
Var(X) = (1 - p) / p^2
```

Implementation notes:

- parameter is `p` with `0 < p <= 1`
- `p = 1` gives a degenerate distribution with mass at `1`
- non-integer `k` and `k < 1` return zero mass
- the implementation counts the trial where the first success occurs, not the number of failures before success

Example:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Distribution\Geometric;

$geometric = Geometric::of('0.5');

echo $geometric->pmf(1)->value()->value() . PHP_EOL; // 0.5

echo $geometric->cdf(2)->value()->value() . PHP_EOL; // 0.75
```

### Poisson

A Poisson variable counts events occurring in a fixed interval when the mean rate is `lambda`.

Support: `{0, 1, 2, ...}`

PMF:

```text
P(X = k) = e^{-lambda} lambda^k / k!
```

Expectation:

```text
E[X] = lambda
```

Variance:

```text
Var(X) = lambda
```

Implementation notes:

- `lambda` must be strictly positive
- non-negative integer inputs are valid
- negative values and non-integer values return zero mass

Example:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Distribution\Poisson;

$poisson = Poisson::of('3');

echo $poisson->pmf(2)->value()->value() . PHP_EOL; // 0.224041807655955...

echo $poisson->cdf(2)->value()->value() . PHP_EOL; // 0.423190081126843...
```

## Continuous distributions

### Exponential

An exponential variable models the waiting time until the next event with rate parameter `lambda`.

Support: `x >= 0`

PDF:

```text
f(x) = lambda * e^{-lambda x},      x >= 0
```

CDF:

```text
F(x) = 1 - e^{-lambda x},         x >= 0
```

Expectation:

```text
E[X] = 1 / lambda
```

Variance:

```text
Var(X) = 1 / lambda^2
```

Implementation notes:

- `rate` must be strictly positive
- negative `x` returns zero density and zero probability

Example:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Distribution\Exponential;

$exp = Exponential::of(2);

echo $exp->pdf(1)->value() . PHP_EOL;
echo $exp->cdf(1)->value()->value() . PHP_EOL;
```

### Normal

A normal variable is parameterized by a mean `mu` and positive standard deviation `sigma`.

Support: all real numbers

PDF:

```text
f(x) = 1 / (sigma * sqrt(2*pi)) * exp(-(x - mu)^2 / (2*sigma^2))
```

CDF:

```text
F(x) = Phi((x - mu) / sigma)
```

The implementation evaluates this through an `erf` approximation based on Abramowitz–Stegun 7.1.26. This is a numerical approximation of the normal CDF.

Expectation:

```text
E[X] = mu
```

Variance:

```text
Var(X) = sigma^2
```

Implementation notes:

- `standardDeviation` must be `> 0`
- this class uses a numerical approximation for `cdf()`

Example:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Distribution\Normal;

$normal = Normal::of(0, 1);

echo $normal->pdf(0)->value() . PHP_EOL;
echo $normal->cdf(0)->value()->value() . PHP_EOL; // 0.5
```

### Uniform

A uniform variable is spread evenly across the interval `[a, b]`.

Support: `[a, b]`

PDF:

```text
f(x) = 1 / (b - a),   for a <= x <= b
```

CDF:

```text
F(x) = 0,                     x <= a
F(x) = (x - a) / (b - a),    a < x < b
F(x) = 1,                     x >= b
```

Expectation:

```text
E[X] = (a + b) / 2
```

Variance:

```text
Var(X) = (b - a)^2 / 12
```

Implementation notes:

- `a < b` is required
- values outside the interval return density `0`
- `cdf(a)` returns `0`
- `cdf(b)` returns `1`

Example:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Distribution\Uniform;

$uniform = Uniform::of(0, 2);

echo $uniform->pdf(1)->value() . PHP_EOL; // 0.5

echo $uniform->cdf(1)->value()->value() . PHP_EOL; // 0.5
```

## Edge cases and implementation behavior

The following edge cases are important and are handled by the current implementation:

- Bernoulli:
  - `p = 0` is allowed
  - `p = 1` is allowed
- Binomial:
  - `n = 0` is allowed
  - `p = 0` is degenerate at `0`
  - `p = 1` is degenerate at `n`
- Geometric:
  - `p = 1` is valid and collapses to a single-point distribution at `1`
  - `k < 1` returns zero mass
  - fractional `k` returns zero mass
- Poisson:
  - `lambda <= 0` is rejected
  - negative `x` gives zero mass
  - fractional `x` gives zero mass
- Exponential:
  - `rate <= 0` is rejected
  - `x < 0` yields zero density and zero probability
- Normal:
  - `standardDeviation <= 0` is rejected
  - CDF is approximated numerically
- Uniform:
  - `a >= b` is rejected
  - `x < a` gives zero density and zero probability
  - `x = a` gives density `1 / (b - a)` but cumulative probability `0`
  - `x = b` gives density `1 / (b - a)` but cumulative probability `1`

## Related modules

- [probability.md](probability.md)
- [statistics.md](statistics.md)
- [number.md](number.md)
