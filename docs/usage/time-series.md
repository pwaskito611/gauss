# Time Series

## Overview

The time series module models ordered observations and supports transformations, smoothing, forecasting metrics, and basic autoregressive and moving-average models. It is built around the immutable `Gauss\TimeSeries\TimeSeries` value and the `Gauss\TimeSeries\Observation` record.

The module provides:

- ordered observations with `TimeSeries` and `Observation`;
- lagging and differencing with `Lag` and `Difference`;
- smoothing with `MovingAverage` and `SimpleExponentialSmoothing`;
- forecast-error metrics with `MAE`, `MAPE`, `MSE`, and `RMSE`;
- autoregressive, moving-average, and joint models with `AR`, `MA`, and `ARMA`.

Values are converted through `Gauss\Number\Number`. By default, calculations
inherit `Number`'s BCMath decimal precision and rounding behavior. Methods that
divide or take square roots are deterministic under those rules, but are not
universally exact. The optional float mode uses native PHP float arithmetic.

Numeric model-fitting, smoothing, differencing, and forecasting-metric entry
points accept a trailing `bool $precision = true`. BCMath remains the default;
pass `false` to select float mode for managed series values and calculations.
Index-only `Lag` does not need a precision selector. See
[Precision modes](precision.md).

## Core types

| Type | Purpose |
| --- | --- |
| `Gauss\TimeSeries\TimeSeries` | Immutable ordered sequence of `Observation` records. |
| `Gauss\TimeSeries\Observation` | Immutable indexed value pair. |
| `Gauss\TimeSeries\Transform\Lag` | Static helper that applies a lag to a series. |
| `Gauss\TimeSeries\Transform\Difference` | Static helper that applies differencing to a series. |
| `Gauss\TimeSeries\Smoothing\MovingAverage` | Trailing moving average with partial start window. |
| `Gauss\TimeSeries\Smoothing\SimpleExponentialSmoothing` | Recursive exponential smoothing with smoothing factor `alpha`. |
| `Gauss\TimeSeries\Metrics\MAE` | Mean absolute error. |
| `Gauss\TimeSeries\Metrics\MAPE` | Mean absolute percentage error. |
| `Gauss\TimeSeries\Metrics\MSE` | Mean squared error. |
| `Gauss\TimeSeries\Metrics\RMSE` | Root mean squared error. |
| `Gauss\TimeSeries\Model\AR` | Least-squares AR(p) fit. |
| `Gauss\TimeSeries\Model\MA` | Iterative least-squares MA(q) fit. |
| `Gauss\TimeSeries\Model\ARMA` | Joint ARMA(p, q) fit with residual iteration. |

## Input model

`TimeSeries::of()` accepts a list of integers, floats, numeric strings, or `Number` values. Each value is normalized with `Number::of()` and stored at its positional index.

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\TimeSeries\TimeSeries;

$series = TimeSeries::of([10, 12, 15, 14, 18, 20]);
```

An empty list throws `InvalidArgumentException`. Prefer strings when preserving a decimal literal matters, since an input float may already contain binary floating-point error.

Metric and model methods also accept either `array` or `TimeSeries` depending on the class. Metrics operate on paired plain arrays; models accept a `TimeSeries` and return `TimeSeries` values.

## Observations

`Observation` stores an index and a `Number` value.

```php
public function index(): int;
public function value(): Number;
```

`TimeSeries` exposes its observations and helpers:

```php
public function observations(): array; // list<Observation>
public function count(): int;
public function first(): Observation;
public function last(): Observation;
public function valueAt(int $index): Number;
public function values(): array; // list<Number>
public function toArray(): array; // list<string>
```

`valueAt()` throws `InvalidArgumentException` when the index is outside the series.

```php
$series = TimeSeries::of([10, 12, 15, 14, 18, 20]);

$series->first()->value()->value(); // "10"
$series->last()->value()->value();  // "20"
$series->valueAt(2)->value();       // "15"
```

## Transformations

### `TimeSeries::lag(int $lag): self`

Returns a new series that shifts observations forward by `lag`. The result contains the values `x[0], x[1], ..., x[n-1-lag]`.

The lag must be non-negative and smaller than the series length; otherwise `InvalidArgumentException` is thrown.

### `TimeSeries::difference(int $order = 1): self`

Returns the series of successive first differences. Higher orders apply the difference repeatedly.

The order must be at least `1`, and the series must have at least two observations at each iteration; otherwise `InvalidArgumentException` is thrown.

### Static helpers

```php
Gauss\TimeSeries\Transform\Lag::apply(TimeSeries $series, int $lag): TimeSeries;
Gauss\TimeSeries\Transform\Difference::apply(TimeSeries $series, int $order = 1): TimeSeries;
```

Both helpers delegate to the matching `TimeSeries` method.

```php
<?php

use Gauss\TimeSeries\TimeSeries;
use Gauss\TimeSeries\Transform\Difference;
use Gauss\TimeSeries\Transform\Lag;

$series = TimeSeries::of([10, 12, 15, 14, 18]);

$lagged = Lag::apply($series, 1);
$differenced = Difference::apply($series, 1);
```

## Statistical helpers

### `TimeSeries::variance(): Number`

Returns the population variance of the series, delegating to `Statistics::populationVariance()`.

### `TimeSeries::isConstant(): bool`

Returns `true` when every observation compares equal to the first observation. A single-observation series is treated as constant.

### `TimeSeries::map(callable $transform): self`

Returns a new series where each value is replaced by `Number::of($transform($observation->value()))`.

### `TimeSeries::slice(int $start, ?int $length = null): self`

Returns a contiguous slice starting at `$start`. When `$length` is `null`, the slice extends to the end. `$start` must be a valid index and `$length`, when supplied, must be positive; otherwise `InvalidArgumentException` is thrown.

### `TimeSeries::acf(int $lag): Number`

Returns the autocorrelation at the requested lag using the biased definition with denominator `n` (the full-series sum of squared deviations), not `n - k`.

- `$lag` must be non-negative and smaller than the series length.
- For a single-observation series, the method returns `1`.
- For a constant series, `lag = 0` returns `1` and any other lag returns `0`.
- `lag = 0` returns `1` for any valid series.

### `TimeSeries::pacf(int $lag): Number`

Returns the partial autocorrelation at the requested lag by solving the lagged normal equations.

- `$lag` must be at least `1` and smaller than the series length.
- The regression design must have a unique solution; otherwise `InvalidArgumentException` is thrown.

```php
<?php

use Gauss\TimeSeries\TimeSeries;

$series = TimeSeries::of([1, 2, 3, 4, 5]);

$variance = $series->variance();
$acf = $series->acf(1);
```

## Smoothing

### `MovingAverage`

Computes a trailing moving average using a partial window at the beginning of the series. With `window = 3`, the first smoothed values are averages over `[x0]`, `[x0, x1]`, and `[x0, x1, x2]`.

```php
public function __construct(int $window);
public static function smooth(TimeSeries $series, int $window): TimeSeries;
public static function of(TimeSeries $series, int $window): TimeSeries;
public function apply(TimeSeries $series): TimeSeries;
```

The window must be at least `1`, and it cannot exceed the series length; otherwise `InvalidArgumentException` is thrown.

### `SimpleExponentialSmoothing`

Applies recursive exponential smoothing with factor `alpha`.

```php
public function __construct(Number $alpha);
public static function smooth(TimeSeries $series, int|float|string|Number $alpha): TimeSeries;
public function apply(TimeSeries $series): TimeSeries;
```

`alpha` must be in the inclusive interval `[0, 1]`; otherwise `InvalidArgumentException` is thrown. The first smoothed value is the first observation. Subsequent values follow `level = alpha * x[t] + (1 - alpha) * level`.

```php
<?php

use Gauss\TimeSeries\Smoothing\MovingAverage;
use Gauss\TimeSeries\Smoothing\SimpleExponentialSmoothing;
use Gauss\TimeSeries\TimeSeries;

$series = TimeSeries::of([10, 12, 15, 14, 18, 20]);

$smooth = MovingAverage::smooth($series, 3);
$exponential = SimpleExponentialSmoothing::smooth($series, '0.4');
```

## Forecast metrics

All metrics share the same input contract:

- `$actual` and `$predicted` must be non-empty lists of integers, floats, numeric strings, or `Number` values;
- both lists must have the same length.

Violations throw `InvalidArgumentException`.

### `MAE::calculate(array $actual, array $predicted): Number`

Returns the mean absolute error: `mean(|actual[i] - predicted[i]|)`.

### `MSE::calculate(array $actual, array $predicted): Number`

Returns the mean squared error: `mean((actual[i] - predicted[i])^2)`.

### `RMSE::calculate(array $actual, array $predicted): Number`

Returns the square root of the mean squared error. It delegates to `MSE::calculate()` and applies `Number::sqrt()`, so it inherits half-up rounding to 50 decimal places.

### `MAPE::calculate(array $actual, array $predicted): Number`

Returns the mean absolute percentage error: `mean(|actual[i] - predicted[i]| / |actual[i]|) * 100`.

`MAPE` treats the metric as undefined when any actual value is zero and throws `InvalidArgumentException` instead of skipping or replacing the zero observation.

```php
<?php

use Gauss\TimeSeries\Metrics\MAE;
use Gauss\TimeSeries\Metrics\MAPE;
use Gauss\TimeSeries\Metrics\MSE;
use Gauss\TimeSeries\Metrics\RMSE;

$actual = [10, 12, 15, 14, 18];
$predicted = [11, 12, 14, 15, 17];

echo MAE::calculate($actual, $predicted)->value() . PHP_EOL;
echo MAPE::calculate($actual, $predicted)->value() . PHP_EOL;
echo MSE::calculate($actual, $predicted)->value() . PHP_EOL;
echo RMSE::calculate($actual, $predicted)->value() . PHP_EOL;
```

## Models

### `AR`

Fits an AR(p) model with an intercept using ordinary least squares on the lagged design matrix.

```php
public static function fit(TimeSeries $series, int $order): self;

public function order(): int;
public function coefficients(): array; // list<Number>
public function intercept(): Number;
public function predict(int $horizon): TimeSeries;
public function residuals(): TimeSeries;
```

The order must be at least `1`, and the series must have more observations than the order; otherwise `InvalidArgumentException` is thrown.

When the normal equations admit a unique solution, the coefficients and intercept are used directly. Otherwise, the model falls back to the series mean as the intercept and zeros for the AR coefficients.

`predict()` produces a `TimeSeries` of length `$horizon` using a recursive forecast that appends each prediction to the history. The horizon must be at least `1`.

`residuals()` returns the in-sample residuals over indices `order .. n-1`.

The model does not enforce stationarity or forecast stability; caller-side validation is the responsibility of the caller.

```php
<?php

use Gauss\TimeSeries\Model\AR;
use Gauss\TimeSeries\TimeSeries;

$series = TimeSeries::of([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

$model = AR::fit($series, 2);

echo $model->intercept()->value() . PHP_EOL;
foreach ($model->coefficients() as $coefficient) {
    echo $coefficient->value() . PHP_EOL;
}

$forecast = $model->predict(3);
```

### `MA`

Fits an MA(q) model by iteratively estimating residuals and refitting the coefficients.

```php
public static function fit(TimeSeries $series, int $order): self;

public function order(): int;
public function coefficients(): array; // list<Number>
public function mean(): Number;
public function predict(int $horizon): TimeSeries;
public function residuals(): TimeSeries;
```

The order must be at least `1`, and the series must have more observations than the order; otherwise `InvalidArgumentException` is thrown.

The fit centers the series on its mean, then iterates up to ten passes. Each pass regresses the centered target on previous residuals. The iteration stops early when the coefficient change is within `0.000000000001`, or when the linear system does not admit a unique solution.

`predict()` appends zero future residuals after the observed ones, so forecasts converge to the series mean as the horizon grows.

`residuals()` recomputes the in-sample residuals from the fitted mean and coefficients.

The model does not enforce invertibility; caller-side validation is the responsibility of the caller.

### `ARMA`

Fits a joint ARMA(p, q) model with an intercept.

```php
public static function fit(TimeSeries $series, int $arOrder, int $maOrder): self;

public function arOrder(): int;
public function maOrder(): int;
public function arCoefficients(): array; // list<Number>
public function maCoefficients(): array; // list<Number>
public function intercept(): Number;
public function predict(int $horizon): TimeSeries;
public function residuals(): TimeSeries;
```

Both orders must be at least `1`. The series must have more observations than `max($arOrder, $maOrder)`, and there must be enough observations to estimate all parameters (`sampleCount - max($arOrder, $maOrder) >= 1 + $arOrder + $maOrder`). Otherwise `InvalidArgumentException` is thrown.

The fit is iterative:

1. build an initial residual estimate using an AR fit on a heuristic order;
2. jointly regress the target on lagged values and lagged residuals;
3. recompute residuals from the updated parameters;
4. repeat up to eight passes, stopping early when the maximum absolute parameter change is at most `0.00000001`.

When a pass does not produce a unique solution, the fit either retries with the refinement start on the first pass or stops.

`predict()` uses the history of observations and residuals to produce a recursive forecast, appending zero residuals for future steps.

`residuals()` returns a `TimeSeries` starting at `max($arOrder, $maOrder)`. The result is cached after the first call.

The model does not enforce stationarity or invertibility; caller-side validation is the responsibility of the caller.

```php
<?php

use Gauss\TimeSeries\Model\ARMA;
use Gauss\TimeSeries\TimeSeries;

$series = TimeSeries::of([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]);

$model = ARMA::fit($series, 1, 1);

$forecast = $model->predict(5);
$residuals = $model->residuals();
```

## Precision model

Time series calculations use `Number` for numeric conversion, arithmetic, and comparison. The exactness and rounding behavior therefore depend on the operations a routine uses. Inputs supplied as floats may already have lost decimal precision before conversion.
The table below describes the default BCMath behavior. Numeric entry points
called with `precision: false` use native floats for the calculations they
manage; see [Precision modes](precision.md).

| Routine / operation | Precision behavior |
| --- | --- |
| `TimeSeries::of()`, `values()`, `toArray()` | Uses exact `Number` conversion and string output. |
| `TimeSeries::lag()`, `difference()` | Uses exact decimal subtraction on `Number` values. |
| `TimeSeries::variance()` | Delegates to `Statistics::populationVariance()`; mean and final division are rounded. |
| `TimeSeries::acf()` | Uses rounded mean and variance, then decimal multiply and rounded division. |
| `TimeSeries::pacf()` | Normal equations built with exact decimal multiplication; solved via `LinearSystem`, which may round during elimination. |
| `MovingAverage` | Partial-window sums use exact decimal addition; each smoothed value uses rounded division. |
| `SimpleExponentialSmoothing` | Uses decimal multiply, add, and subtract; no division. |
| `MAE`, `MSE` | Sums use exact decimal arithmetic; final division is rounded. |
| `RMSE` | Inherits `MSE` rounding, then applies `Number::sqrt()` with half-up rounding to 50 decimal places. |
| `MAPE` | Differences and absolute values use decimal arithmetic; ratio and final mean use rounded division. |
| `AR` | Normal equations use exact decimal multiplication; solution precision follows `LinearSystem`. |
| `MA` | Working scale is 50 decimals; residual and coefficient updates are rounded to that scale each iteration. |
| `ARMA` | Working scale is 20 decimals; parameter updates are rounded to that scale each iteration. |

`div()` uses Gauss's rounded decimal division; `sqrt()` uses half-up rounding to 50 decimal places. Statistical calculations that involve either operation are deterministic under those rules, but should not be described as universally exact.

## Exceptions and edge cases

| API | Exception | Condition |
| --- | --- | --- |
| `TimeSeries::of()` | `InvalidArgumentException` | Empty list of values. |
| `TimeSeries::valueAt()` | `InvalidArgumentException` | Index is outside `[0, count)`. |
| `TimeSeries::slice()` | `InvalidArgumentException` | Start is outside `[0, count)` or length is not positive. |
| `TimeSeries::lag()` | `InvalidArgumentException` | Lag is negative or not smaller than the series length. |
| `TimeSeries::difference()` | `InvalidArgumentException` | Order is less than `1` or series has fewer than two observations at some iteration. |
| `TimeSeries::acf()` | `InvalidArgumentException` | Lag is negative or not smaller than the series length. |
| `TimeSeries::pacf()` | `InvalidArgumentException` | Lag is less than `1`, not smaller than the series length, or the regression has no unique solution. |
| `MovingAverage::__construct()` / `smooth()` / `of()` / `apply()` | `InvalidArgumentException` | Window is less than `1` or exceeds the series length. |
| `SimpleExponentialSmoothing::__construct()` / `smooth()` | `InvalidArgumentException` | `alpha` is outside `[0, 1]`. |
| `MAE`, `MSE`, `RMSE`, `MAPE` | `InvalidArgumentException` | Empty input or mismatched lengths. |
| `MAPE` | `InvalidArgumentException` | Any actual value is zero. |
| `AR::fit()` | `InvalidArgumentException` | Order is less than `1` or series length is not greater than the order. |
| `AR::predict()` | `InvalidArgumentException` | Horizon is less than `1`. |
| `MA::fit()` | `InvalidArgumentException` | Order is less than `1` or series length is not greater than the order. |
| `MA::predict()` | `InvalidArgumentException` | Horizon is less than `1`. |
| `ARMA::fit()` | `InvalidArgumentException` | Either order is less than `1`. |
| `ARMA::fit()` | `InvalidArgumentException` | Series length is not greater than `max($arOrder, $maOrder)`. |
| `ARMA::fit()` | `InvalidArgumentException` | Not enough observations to estimate all parameters. |
| `ARMA::predict()` | `InvalidArgumentException` | Horizon is less than `1`. |

The models do not check stationarity, invertibility, or forecast stability. Callers are responsible for validating whether a fitted model is appropriate for their data.

## Complete example

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\TimeSeries\Metrics\MAE;
use Gauss\TimeSeries\Metrics\MSE;
use Gauss\TimeSeries\Metrics\RMSE;
use Gauss\TimeSeries\Model\AR;
use Gauss\TimeSeries\Model\ARMA;
use Gauss\TimeSeries\Model\MA;
use Gauss\TimeSeries\Smoothing\MovingAverage;
use Gauss\TimeSeries\Smoothing\SimpleExponentialSmoothing;
use Gauss\TimeSeries\TimeSeries;
use Gauss\TimeSeries\Transform\Difference;
use Gauss\TimeSeries\Transform\Lag;

$series = TimeSeries::of([10, 12, 15, 14, 18, 20, 22, 25, 24, 28]);

$lagged = Lag::apply($series, 1);
$differenced = Difference::apply($series, 1);

$variance = $series->variance();
$acf1 = $series->acf(1);
$pacf1 = $series->pacf(1);

$smooth = MovingAverage::smooth($series, 3);
$exponential = SimpleExponentialSmoothing::smooth($series, '0.4');

$actual = $series->toArray();
$predicted = $smooth->toArray();

$mae = MAE::calculate($actual, $predicted);
$mse = MSE::calculate($actual, $predicted);
$rmse = RMSE::calculate($actual, $predicted);

$ar = AR::fit($series, 2);
$ma = MA::fit($series, 1);
$arma = ARMA::fit($series, 1, 1);

$arForecast = $ar->predict(3);
$maForecast = $ma->predict(3);
$armaForecast = $arma->predict(3);

$arResiduals = $ar->residuals();
$maResiduals = $ma->residuals();
$armaResiduals = $arma->residuals();
```

## Related modules

- [statistics.md](statistics.md)
- [linear.md](linear.md)
- [number.md](number.md)
- [optimization.md](optimization.md)