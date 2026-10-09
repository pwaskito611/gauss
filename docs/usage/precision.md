# Precision modes

Gauss uses BCMath-backed decimal arithmetic by default. For supported numeric
operations, callers can explicitly select native PHP `float` arithmetic when
that trade-off is appropriate. Float mode is opt-in; Gauss does not switch
backend automatically based on input size or performance estimates.

## `Number`

`Number` is immutable. `offPrecision()` returns a float-mode copy, leaving the
original number unchanged:

```php
use Gauss\Number\Number;

$decimal = Number::of('0.1');
$float = $decimal->offPrecision();

echo $decimal->backend(); // bcmath
echo $float->backend();   // float
echo $float->add('0.2')->backend(); // float
```

Arithmetic results use float mode if an operand uses float mode. `backend()`
reports `"bcmath"` or `"float"` and is marked `@internal`; use it in tests and
diagnostics, not as an application-level branching contract.

## Objects with `offPrecision()`

Numeric value objects expose `offPrecision()` and return converted copies. For
example, vectors, matrices, polynomials, geometry values, distributions,
probability data, time series, and numeric sequence objects can be prepared
before further operations:

```php
use Gauss\Algebra\Polynomial;
use Gauss\Number\Number;

$polynomial = Polynomial::of([
    0 => Number::of(1),
    1 => Number::of(2),
    2 => Number::of(3),
]);

$result = $polynomial->offPrecision()->evaluate(Number::of(2));

echo $result->value();   // 17
echo $result->backend(); // float
```

This does not mutate the source object. It also does not modify global state or
change the backend of unrelated computations.

## Static numeric APIs

Static numeric entry points that support backend selection take a trailing
`bool $precision = true` argument. `true` selects BCMath and is the default;
`false` selects float mode:

```php
use Gauss\Statistics\Statistics;
use Gauss\TimeSeries\Metrics\MAE;

$decimalMean = Statistics::mean([1, 2, 3]);
$floatMean = Statistics::mean([1, 2, 3], precision: false);

$decimalMae = MAE::calculate([1, 2], [2, 4]);
$floatMae = MAE::calculate([1, 2], [2, 4], precision: false);

echo $decimalMean->backend(); // bcmath
echo $floatMean->backend();   // float
echo $decimalMae->backend();  // bcmath
echo $floatMae->backend();    // float
```

The flag is supported on:

- **Statistics:** numeric descriptive, dispersion, quantile, moment,
  covariance/correlation, matrix, and weighted methods. `count()` remains an
  integer operation.
- **Probability:** `ProbabilityMeasure::of()`, `fromMap()`, and `uniform()`;
  `RandomVariable::of()` and `fromMap()`; and `Expectation::of()` and
  `Variance::of()`.
- **Distribution:** factories for built-in distributions
  (`Bernoulli::of()`, `Binomial::of()`, `Exponential::of()`,
  `Geometric::of()`, `Normal::of()`, `Poisson::of()`, and `Uniform::of()`).
- **Numerical:** root solvers, finite differences, integration, and
  interpolation methods.
- **Optimization:** one- and multidimensional search methods.
- **Time series:** model `fit()` methods, smoothing and differencing helpers,
  and forecast metrics (`MAE`, `MAPE`, `MSE`, and `RMSE`).

The new parameter is appended after existing parameters. Existing calls that
omit it continue to use BCMath.

## Propagation and callbacks

When a supported Gauss operation is given `precision: false`, the backend is
applied to the numeric inputs it owns and carried through its Gauss-managed
intermediate calculations. Optimizers and numerical methods pass float-mode
arguments to callbacks and rebind callback results when they return to the
algorithm.

Gauss cannot control calculations performed inside user callback code that
start independent BCMath values or otherwise ignore the supplied float-mode
arguments. To keep callback calculations in float mode, derive results from
the provided `Number` or `Vector` values.

## Exact/discrete operations

Operations whose contract depends on exact integer or index semantics are not
made approximate by this feature. Combinatorics, number theory, set operations,
indices, and counters remain exact/discrete operations. Numeric sequence
values (arithmetic, geometric, and recurrence sequences) can use `offPrecision()`;
their indices remain integer-validated.

## Choosing a backend

Native floats have finite binary precision. They can accumulate rounding error,
overflow, or underflow; converting a float result back to a decimal string does
not restore information lost during computation. Use the default BCMath backend
when decimal representation and reproducible decimal arithmetic matter. Use
float mode only when its numerical behavior is acceptable for the workload.

The [technical precision notes](../technical/precision.md) describe numeric
limitations, and the [off-precision audit](../technical/off-precision-audit.md)
records a local benchmark and its workload-specific result differences.
