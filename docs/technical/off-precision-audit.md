# Off-precision audit

## Design

`Number` is immutable. BCMath remains its default backend; `Number::offPrecision()`
returns a copy marked for native-float arithmetic. Arithmetic results inherit float
mode from either operand, so a mode-enabled value carries the choice through normal
`Number` operations without process-wide state. `withBackend()` is an internal
rebinding helper used at Gauss integration boundaries.

Container and model `offPrecision()` methods return copies and recursively convert
their owned numeric values. They do not mutate a caller's original object. Numeric
static entry points that support backend selection take a final
`bool $precision = true`: `true` binds managed operands and intermediates to BCMath;
`false` binds them to native float. This per-call choice does not change global
state or leak into independent calls. Integer indices and exact discrete algorithms
are not converted to floats.

For callbacks, Gauss passes mode-enabled numeric arguments and rebinds plain
BCMath callback results to the input mode when they re-enter a managed algorithm.
Gauss cannot control arithmetic performed inside user callback code. Callbacks
should derive results from their supplied numeric arguments (rather than start a
separate BCMath-only calculation) when float mode is required.

## Class inventory

The audit covered all 95 PHP source files under `src/`.

| Module | Classes and status | Dependency / action |
|---|---|---|
| Number | `Number`: explicit backend selection and arithmetic; `Decimal`: BCMath-only helper | `Number` validates values before float conversion and rejects out-of-range/underflowing conversions. `Decimal` stays the exact default implementation. |
| Algebra | `Monomial`, `Polynomial`: explicit mode propagation; `PolynomialDivision`: static operation, consumes those values | Polynomial terms and coefficients carry mode into `Number`. `PolynomialDivision` does not own a computation context. |
| Linear | `Vector`, `Matrix`, `LinearEquation`, `LinearMap`, `LinearSystem`, `RowReduction`, `Eigen`, `LUDecomposition`, `QRDecomposition`, `VectorSpace`, `UniqueSolution`, `InfiniteSolutions`: explicit propagation; `LinearSystemSolution` and `NoSolution`: no numeric state | Matrix/vector values propagate through decomposition and solve operations. Decompositions rerun from a float-mode source matrix when converted. |
| Geometry | `Point`, `Line`, `Circle`, `Segment`: explicit propagation | Fixed the identified gap: line projections, intersections and distances carry mode through `Point`/`Vector` to `Number`; circle constants use the radius' backend. |
| Probability | `Probability`, `ProbabilityMeasure`, `RandomVariable`, `Expectation`, `Variance`, `ConditionalProbability`: explicit propagation; `Event`, `SampleSpace`, `OutcomeIdentity`: discrete bookkeeping | Measure and random-variable factories, expectation, and variance accept the selector. Selected backend is copied through both dependencies; event/sample-space bookkeeping remains discrete. |
| Distribution | `Bernoulli`, `Binomial`, `Exponential`, `Geometric`, `Normal`, `Poisson`, `Uniform`: explicit mode and precision-aware factories; `Distribution`, `ContinuousDistribution`, `DiscreteDistribution`, `DiscreteIndexSupport`: contracts/helpers | Built-in factories bind parameters using final `$precision = true`. The existing distribution interface is unchanged for downstream implementors. `Normal` also rebinds internally-created coefficients and constants before numeric evaluation. |
| Statistics | `Statistics`: precision-aware static functions, no per-instance mode | Numeric statistics accept final `$precision = true`; false binds input values, weights, probabilities, and matrix data to float. `count()` remains integer-valued. |
| Numerical | `BackwardDifference`, `CentralDifference`, `ForwardDifference`, `SimpsonRule`, `TrapezoidalRule`, `LagrangeInterpolation`, `LinearInterpolation`, `Bisection`, `NewtonRaphson`, `Secant`: precision-aware stateless functions | The selector reaches inputs, intermediate values, callback arguments, and callback results. Arithmetic inside user callbacks remains caller-controlled. |
| Optimization | `BoxConstraint`, `OptimizationResult`: explicit propagation; `NelderMead`, `CoordinateDescent`, `GradientDescent`, `GoldenSectionSearch`: precision-aware static algorithms | Optimizers bind initial points, bounds, tolerances, candidates, and callback results using the selected backend. |
| Time series | `Observation`, `TimeSeries`, `AR`, `ARMA`, `MA`, `SimpleExponentialSmoothing`: explicit propagation; `MovingAverage`, `Difference`, `MAE`, `MAPE`, `MSE`, `RMSE`: precision-aware operations; `Lag`: index-only | Model fitting, smoothing, differencing, and metrics accept a trailing selector and propagate it through series values and calculations. |
| Discrete | `ArithmeticSequence`, `GeometricSequence`, `Recurrence`: explicit propagation for numeric sequence values; `Combination`, `Factorial`, `Multinomial`, `Permutation`, `Congruence`, `Coprime`, `Divisibility`, `DivisorFunctions`, `EulerTotient`, `ExtendedGCD`, `Factorization`, `GCD`, `IntegerSquareRoot`, `LCM`, `ModularArithmetic`, `ModularInverse`, `Prime`, `Relation`, `Set`: exact/discrete operations | Number-theory and combinatoric routines retain integer/BCMath semantics; forcing these through float would risk changing their mathematical contract. `Recurrence` passes mode-enabled terms to its callback and rebinds returned terms. |

The selector is only added to static entry points that perform numeric arithmetic.
Count/index operations, discrete combinatorics, and number-theory operations remain
exact and do not accept a misleading float selector. Parameters are trailing and
default to `true`, preserving existing positional calls and BCMath behavior.

For the public API examples and guidance on choosing a backend, see the
[precision mode usage guide](../usage/precision.md).

## Numeric behavior and limitations

- BCMath remains the default. A normal `Number` operation or independent object is
  not changed by enabling float mode on another immutable value/object.
- Native float conversion rejects values outside the finite float range and
  nonzero values that underflow during conversion. Division by zero, negative
  square roots, invalid exponents, and non-finite results remain explicit errors.
- Float modulo keeps the integer-only contract and returns Euclidean remainders.
- Float results are serialized as round-trip decimal strings. Converting that
  string back to BCMath does not restore precision already lost to float rounding.
- Exact integer/index semantics remain exact; use strings/BCMath when those
  semantics or decimal precision are required.

## Benchmark

Run with `php benchmarks/off-precision.php`. The script times the same workload
on BCMath and float values and reports the absolute difference between the final
decimal representations. One local run on PHP 8.2.34 produced:

| Workload | Iterations | BCMath ms | Float ms | Float/BCMath time | Absolute result difference |
|---|---:|---:|---:|---:|---:|
| Arithmetic chain | 100 | 344.912 | 263.537 | 0.764 | 3e-16 |
| Matrix multiply | 30 | 7.712 | 6.396 | 0.829 | 2e-16 |
| Statistics variance | 100 | 394.757 | 197.337 | 0.500 | 4e-18 |
| Normal CDF | 20 | 137.885 | 3.227 | 0.023 | 1.47e-16 |
| Time-series forecast | 40 | 148.070 | 2.950 | 0.020 | 2.85e-16 |
| Golden-section search | 2 | 13.569 | 2.784 | 0.205 | 4.84e-16 |

This is a microbenchmark from one run, not a performance guarantee. Setup is
outside timed sections; timings vary by PHP build and host. The reported
differences are the measured error for these selected inputs only.
