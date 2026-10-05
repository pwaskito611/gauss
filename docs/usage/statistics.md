# Statistics

## Overview

`Gauss\Statistics\Statistics` provides descriptive, dispersion, quantile,
moment, relationship, matrix, and weighted statistics. Most methods accept
observations as either a PHP array or a `Gauss\Linear\Vector`; methods
operating on multiple datasets require matching observation counts.
`covarianceMatrix()` and `correlationMatrix()` take a `Gauss\Linear\Matrix`.

Values are converted through `Gauss\Number\Number`. Some calculations use
exact decimal addition, subtraction, multiplication, comparison, and integer
powers. Calculations that divide or take square roots inherit `Number`'s
rounded decimal precision; not every statistical result is exact.

## Input model

An array and a `Vector` can represent the same observations:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Linear\Vector;
use Gauss\Statistics\Statistics;

$fromArray = Statistics::mean([1, 2, 3, 4, 5]);
$fromVector = Statistics::mean(Vector::of(1, 2, 3, 4, 5));
```

An input array may contain integers, floats, numeric strings, and `Number`
values. Each observation is converted using `Number::of()`. Prefer strings
when preserving a decimal literal matters, since an input float may already
contain binary floating-point error.

Except for `count()`, which returns `0` for empty input, methods requiring
observations reject empty data with `InvalidArgumentException`. For methods
using paired datasets, each dataset must be non-empty and both lengths must
match.

## Descriptive statistics

All mathematical results in this section return `Number`; `count()` returns
an integer.

### `count(array|Vector $data): int`

Returns the number of observations. Unlike the other methods, it accepts an
empty input and returns zero.

```php
Statistics::count([1, 2, 3]); // 3
Statistics::count([]);        // 0
```

### `sum(array|Vector $data): Number`

Adds all observations in the `Number` domain.

```php
Statistics::sum([1, 2, 3])->value(); // "6"
```

### `mean(array|Vector $data): Number`

Returns the arithmetic mean, `sum(data) / count(data)`. The division uses
`Number::div()` and its rounded precision behavior.

```php
Statistics::mean([1, 2, 3, 4])->value(); // "2.5"
```

### `median(array|Vector $data): Number`

Sorts values numerically. For an odd number of observations it returns the
middle value; for an even number it returns the mean of the two central
values, using rounded division by two.

```php
Statistics::median([9, 1, 4])->value();    // "4"
Statistics::median([1, 2, 8, 9])->value(); // "5"
```

### `mode(array|Vector $data): Number`

Returns a value with the highest frequency. Numeric representations that
normalize to the same `Number` value are grouped together. If frequencies
tie, the method returns the value that first reaches the maximum frequency
while traversing the input.

```php
Statistics::mode([1, 1, 2, 2])->value(); // "1"
```

### `min(array|Vector $data): Number`

Returns the numerically smallest observation.

```php
Statistics::min([4, -2, 7])->value(); // "-2"
```

### `max(array|Vector $data): Number`

Returns the numerically largest observation.

```php
Statistics::max([4, -2, 7])->value(); // "7"
```

### `range(array|Vector $data): Number`

Returns `max(data) - min(data)`.

```php
Statistics::range([4, -2, 7])->value(); // "9"
```

## Variance and deviation

### `populationVariance(array|Vector $data): Number`

Returns the population variance: the sum of squared deviations from the
arithmetic mean divided by `N`, the number of observations. The mean and final
division use `Number::div()`.

```php
Statistics::populationVariance([1, 2, 3, 4])->value(); // "1.25"
```

### `sampleVariance(array|Vector $data): Number`

Returns the sample variance, dividing the squared-deviation sum by `N - 1`
(Bessel's correction), not by `N`. At least two observations are required;
otherwise `InvalidArgumentException` is thrown.

```php
Statistics::sampleVariance([1, 2, 3, 4])->value();
// "1.666666666666666666666666666666666666666666666666666666666667"

Statistics::sampleVariance([5]); // InvalidArgumentException
```

### `populationStandardDeviation(array|Vector $data): Number`

Returns the square root of the population variance. The square root uses
`Number::sqrt()` and is rounded half-up to 50 decimal places.

```php
Statistics::populationStandardDeviation([1, 2, 3, 4])->value();
// "1.11803398874989484820458683436563811772030917980576"
```

### `sampleStandardDeviation(array|Vector $data): Number`

Returns the square root of the sample variance, using denominator `N - 1`
before taking the square root. It requires at least two observations and
inherits the same `InvalidArgumentException` as `sampleVariance()`.

```php
Statistics::sampleStandardDeviation([1, 2, 3, 4])->value();
// "1.29099444873580562839308846659413320361097390176386"
```

### `meanAbsoluteDeviation(array|Vector $data): Number`

Returns the mean of the absolute deviations from the arithmetic mean:
`sum(abs(x - mean)) / N`. Mean and final divisions use rounded
`Number::div()` arithmetic.

```php
Statistics::meanAbsoluteDeviation([1, 2, 3, 4])->value(); // "1"
```

### `medianAbsoluteDeviation(array|Vector $data): Number`

Computes the median, takes each observation's absolute distance from that
median, and returns the median of those deviations. This is the unscaled
median absolute deviation.

```php
Statistics::medianAbsoluteDeviation([1, 2, 3, 4])->value(); // "1"
```

For even-sized data, either median step may divide by two and inherit
`Number::div()` rounding.

### `interquartileRange(array|Vector $data): Number`

Returns the third quartile minus the first quartile, using the same linear
quantile interpolation as `quantile()`.

```php
Statistics::interquartileRange([1, 2, 3, 4])->value(); // "1.5"
```

The quartile probabilities are formed with `Number::div()`, so their
representation follows its precision behavior.

### `coefficientOfVariation(array|Vector $data): Number`

Returns the population standard deviation divided by the arithmetic mean. It
uses population, not sample, standard deviation. A zero mean makes the ratio
undefined and throws `DivisionByZeroError`.

```php
Statistics::coefficientOfVariation([1, 2, 3, 4])->value();
// "0.44721359549995793928183473374625524708812367192231"

Statistics::coefficientOfVariation([-1, 1]); // DivisionByZeroError
```

## Quantiles

`quantile()` sorts observations and uses the linear position
`p * (N - 1)`. When this position is fractional, the adjacent ordered values
are linearly interpolated using `Number` decimal arithmetic.

### `quantile(array|Vector $data, int|float|string|Number $probability): Number`

Probability must be in the inclusive interval `[0, 1]`; otherwise
`InvalidArgumentException` is thrown. The probability is converted through
`Number::of()`.

```php
Statistics::quantile([1, 2, 3, 4], '0.25')->value(); // "1.75"
Statistics::quantile([1, 2, 3, 4], 1)->value();      // "4"
```

### `percentile(array|Vector $data, int|float|string|Number $percentile): Number`

Treats the input as a percentile and delegates to `quantile()` with
`percentile / 100`. Consequently, percentile values must correspond to a
probability in `[0, 1]` (from 0 through 100 inclusive); invalid values are
rejected by `quantile()` with `InvalidArgumentException`.

```php
Statistics::percentile([1, 2, 3, 4], 75)->value(); // "3.25"
```

### `quartile(array|Vector $data, int $quartile): Number`

Accepts an integer quartile index from 0 through 4, inclusive, and returns
the corresponding quantile at `quartile / 4`. Other values throw
`InvalidArgumentException`.

```php
Statistics::quartile([1, 2, 3, 4], 1)->value(); // "1.75"
```

### `decile(array|Vector $data, int $decile): Number`

Accepts an integer decile index from 0 through 10, inclusive, and returns
the corresponding quantile at `decile / 10`. Other values throw
`InvalidArgumentException`.

```php
Statistics::decile([1, 2, 3, 4], 5)->value(); // "2.5"
```

## Moments and shape

### `moment(array|Vector $data, int $order): Number`

Returns the raw moment of the specified non-negative integer order:
`mean(x^order)`. Negative orders throw `InvalidArgumentException`. Powers use
integer `Number::pow()` and the final mean uses division.

```php
Statistics::moment([1, 2, 3], 2)->value();
// "4.666666666666666666666666666666666666666666666666666666666667"
```

### `centralMoment(array|Vector $data, int $order): Number`

Returns the central moment `mean((x - mean(x))^order)`. The order must be
non-negative; negative orders throw `InvalidArgumentException`.

```php
Statistics::centralMoment([1, 2, 3], 2)->value();
// "0.666666666666666666666666666666666666666666666666666666666667"
```

### `skewness(array|Vector $data): Number`

Returns the population standardized third central moment:
`centralMoment(data, 3) / populationStandardDeviation(data)^3`. Zero variance
makes the statistic undefined and throws `DivisionByZeroError`.

```php
Statistics::skewness([1, 2, 3])->value(); // "0"
```

### `kurtosis(array|Vector $data): Number`

Returns Pearson kurtosis, `centralMoment(data, 4) / populationVariance(data)^2`.
This is not sample-bias-corrected kurtosis. Zero variance throws
`DivisionByZeroError`.

```php
Statistics::kurtosis([1, 2, 3])->value(); // "1.5"
```

### `excessKurtosis(array|Vector $data): Number`

Returns Pearson kurtosis minus 3, without sample bias correction. It inherits
`kurtosis()`'s zero-variance `DivisionByZeroError`.

```php
Statistics::excessKurtosis([-1, 0, 0, 0, 0, 1])->value();
// approximately zero; rounded Number operations can leave a tiny residual
```

## Covariance and correlation

### `covariance(array|Vector $left, array|Vector $right, bool $sample = false): Number`

Computes covariance for paired observations after subtracting each dataset's
mean. By default it returns population covariance, dividing by `N`; set
`$sample` to `true` for sample covariance, dividing by `N - 1`. Inputs must
have matching, non-empty lengths. Sample covariance requires at least two
observations. Violations throw `InvalidArgumentException`.

```php
$x = [1, 2, 3];
$y = [2, 4, 6];

Statistics::covariance($x, $y)->value(); // "1.3333..." (population)
Statistics::covariance($x, $y, sample: true)->value(); // "2"
```

Covariance uses rounded divisions for means and the final denominator.

### `correlation(array|Vector $left, array|Vector $right): Number`

Returns population covariance divided by the product of population standard
deviations. The datasets must be non-empty and have the same length. If
either has zero variance, `DivisionByZeroError` is thrown.

```php
Statistics::correlation([1, 2, 3], [2, 4, 6])->value(); // approximately "1"
```

The calculation uses square roots and division and follows `Number`'s
rounding behavior.

## Matrix statistics

For matrix input, rows are observations and columns are variables. A matrix
with `m` rows and `n` columns therefore contains `m` observations for each of
`n` variables. The matrix itself must be non-empty.

```php
use Gauss\Linear\Matrix;

$data = Matrix::of([
    [1, 2],
    [2, 4],
    [3, 6],
]);
// Variable 1 is [1, 2, 3]; variable 2 is [2, 4, 6].
```

### `covarianceMatrix(Matrix $data, bool $sample = false): Matrix`

Returns an `n × n` matrix whose `(i, j)` entry is the covariance between
columns `i` and `j`. It uses population covariance by default; `$sample: true`
uses sample covariance. Sample covariance requires at least two rows;
otherwise `InvalidArgumentException` is thrown.

```php
$covariance = Statistics::covarianceMatrix($data);
$sampleCovariance = Statistics::covarianceMatrix($data, sample: true);
```

### `correlationMatrix(Matrix $data): Matrix`

Returns an `n × n` matrix of population correlations between columns.
Each variable must have non-zero variance; otherwise
`DivisionByZeroError` is thrown.

```php
$correlation = Statistics::correlationMatrix($data);
```

As with `correlation()`, matrix correlations use square roots and division.

## Weighted statistics

Weighted methods pair each observation with the weight at the same position.
Data and weights may each be arrays or `Vector` objects, but both must be
non-empty and have matching lengths. Weights must be non-negative and their
total must be non-zero. Length or negative-weight violations throw
`InvalidArgumentException`; zero total weight throws `DivisionByZeroError`.

### `weightedMean(array|Vector $data, array|Vector $weights): Number`

Returns `sum(data[i] * weights[i]) / sum(weights)`.

```php
Statistics::weightedMean([1, 2, 3], [1, 1, 2])->value(); // "2.25"
```

### `weightedVariance(array|Vector $data, array|Vector $weights): Number`

Returns weighted population variance: the weighted squared deviations from
the weighted mean divided by the sum of weights. There is no sample
correction.

```php
Statistics::weightedVariance([1, 2, 3], [1, 1, 2])->value(); // "0.6875"
```

## Precision model

Statistics uses `Number` for numeric conversion, arithmetic, and comparison.
The exactness and rounding behavior therefore depend on the operations a
statistic uses; inputs supplied as floats may already have lost decimal
precision before conversion.

| Statistic / operation | Precision behavior |
| --- | --- |
| `count()` | Integer count; no numeric arithmetic |
| `sum()` | Exact addition on normalized `Number` values |
| `min()`, `max()`, `mode()` | Exact comparisons on normalized `Number` values |
| `range()` | Exact subtraction of the selected values |
| `mean()` | Rounded `Number::div()` |
| `median()` | Exact selection for odd length; rounded division by 2 for even length |
| `populationVariance()`, `sampleVariance()` | Mean and final division are rounded; integer powers and finite decimal arithmetic otherwise use `Number` |
| `populationStandardDeviation()`, `sampleStandardDeviation()` | Variance calculation plus `Number::sqrt()` rounded half-up to 50 decimal places |
| `meanAbsoluteDeviation()` | Rounded mean and final division |
| `medianAbsoluteDeviation()` | Inherits median selection/interpolation precision |
| `interquartileRange()` | Quantile interpolation with probabilities formed using `Number::div()` |
| `coefficientOfVariation()` | Population variance, rounded square root, and rounded division |
| `quantile()` | Probability positioning and linear interpolation use decimal multiply/add/subtract; no division is performed by the quantile interpolation itself |
| `percentile()`, `quartile()`, `decile()` | Convert their index to a probability using rounded division, then use `quantile()` |
| `moment()`, `centralMoment()` | Integer powers and summation; mean/final division is rounded |
| `skewness()` | Central moment, rounded square root, integer power, and rounded division |
| `kurtosis()`, `excessKurtosis()` | Central moments and rounded divisions; excess subtracts 3 |
| `covariance()` | Means and covariance denominator use rounded division |
| `correlation()` | Covariance, rounded square roots, and rounded division |
| `covarianceMatrix()` | Applies population or sample covariance to each pair of columns |
| `correlationMatrix()` | Applies correlation, including rounded square roots and division, to each pair of columns |
| `weightedMean()`, `weightedVariance()` | Weighted arithmetic uses `Number`; weighted means and variances use rounded division |

Exact decimal operations refer to the normalized values held by `Number`.
`div()` uses Gauss's rounded decimal division; `sqrt()` uses half-up rounding
to 50 decimal places. Statistical calculations that involve either operation
are deterministic under those rules, but should not be described as
universally exact.

## Exceptions and edge cases

| API | Exception | Condition |
| --- | --- | --- |
| `count()` | — | Empty input is allowed and returns `0` |
| Statistical methods requiring observations | `InvalidArgumentException` | Empty array input; empty vectors cannot be constructed |
| Statistical methods converting observations | `InvalidArgumentException` | An observation or probability is not a valid finite `Number` input |
| `sampleVariance()` / `sampleStandardDeviation()` | `InvalidArgumentException` | Fewer than two observations |
| `quantile()` | `InvalidArgumentException` | Probability outside `[0, 1]` |
| `percentile()` | `InvalidArgumentException` | Percentile converts to a probability outside `[0, 1]` |
| `quartile()` | `InvalidArgumentException` | Quartile outside `[0, 4]` |
| `decile()` | `InvalidArgumentException` | Decile outside `[0, 10]` |
| `moment()` / `centralMoment()` | `InvalidArgumentException` | Negative order |
| `covariance()` | `InvalidArgumentException` | Empty data, mismatched lengths, or sample input with fewer than two observations |
| `correlation()` | `InvalidArgumentException` | Empty input or mismatched lengths |
| `correlation()` | `DivisionByZeroError` | Either variable has zero variance |
| `covarianceMatrix(sample: true)` | `InvalidArgumentException` | Fewer than two observations (matrix rows) |
| `correlationMatrix()` | `DivisionByZeroError` | Any variable has zero variance |
| `weightedMean()` / `weightedVariance()` | `InvalidArgumentException` | Empty input, mismatched lengths, or negative weight |
| `weightedMean()` / `weightedVariance()` | `DivisionByZeroError` | Total weight is zero |
| `coefficientOfVariation()` | `DivisionByZeroError` | Mean is zero |
| `skewness()` / `kurtosis()` / `excessKurtosis()` | `DivisionByZeroError` | Variance is zero |

For multiple datasets, values are paired by their array/vector order. Matrix
statistics use columns as variables; the number of observations is the row
count.

## Complete example

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Linear\Matrix;
use Gauss\Linear\Vector;
use Gauss\Statistics\Statistics;

$observations = Vector::of(1, 2, 3, 4);

$summary = [
    'count' => Statistics::count($observations),
    'mean' => Statistics::mean($observations)->value(),
    'median' => Statistics::median($observations)->value(),
    'sample_variance' => Statistics::sampleVariance($observations)->value(),
    'q3' => Statistics::quartile($observations, 3)->value(),
];

$paired = [
    'covariance' => Statistics::covariance([1, 2, 3], [2, 4, 6])->value(),
    'correlation' => Statistics::correlation([1, 2, 3], [2, 4, 6])->value(),
];

$data = Matrix::of([
    [1, 2],
    [2, 4],
    [3, 6],
]);
$covariances = Statistics::covarianceMatrix($data);
$correlations = Statistics::correlationMatrix($data);
```

## Related modules

- [number.md](number.md)
- [linear.md](linear.md)
- [probability.md](probability.md)
