# GAUSS.md — agent guide for `pwaskito611/gauss` (PHP)

Purpose: use the library correctly with minimal tokens. Read this first. Do NOT read README or full docs unless something is missing here.

## Facts
- Decimal-first math toolkit. PHP >= 8.2 + BCMath (default backend). Root namespace `Gauss\`. Default branch: `master` (not `main`). Tests: `composer test`.
- Install: README says `composer require pandu/gauss` (repo is `pwaskito611/gauss`). Check `composer.json` "name" first.
- Everything is **immutable**; ignoring a return value = bug.
- Never do math with native floats/operators on values that came from Gauss. Stay in `Number`. Pass **numeric strings** for decimal literals (`'0.1'`, not `0.1`): a float may already carry binary error before Gauss sees it.
- Do not invent methods. If not listed here: grep `src/` or fetch ONE doc page (see "Lookup").

## Namespaces
```php
use Gauss\Number\Number;
use Gauss\Probability\{Probability, SampleSpace, Event, ProbabilityMeasure, RandomVariable, Expectation, Variance, ConditionalProbability};
use Gauss\Distribution\{Bernoulli, Binomial, Geometric, Poisson, Exponential, Normal, Uniform};
use Gauss\Linear\{Vector, Matrix, LinearEquation, LinearSystem, LUDecomposition, QRDecomposition, Eigen, LinearMap, VectorSpace, RowReduction};
use Gauss\Linear\Solution\{UniqueSolution, InfiniteSolutions, NoSolution};
use Gauss\Statistics\Statistics;
use Gauss\Algebra\Polynomial;
```
Other modules exist (not covered here): `Algebra`, `Geometry`, `Numerical`, `Optimization`, `TimeSeries`, `Discrete`.

## Layering (build upward, don't skip layers)
`Number → Probability → Distribution → Vector/Matrix → Numerical → model`. Compose primitives; there is no big prebuilt model class (e.g. no `Hsmm`).

## Number
- Create: `Number::of(int|float|numeric-string|Number)` (accepts `'1.25e-3'`; normalizes: `'001.2300'`→`'1.23'`, zero→`'0'`; passing a `Number` returns same instance). `Number::pi()`, `Number::e()` (50-dp approximations), `$n->one()`.
- Arithmetic (operand may be int|float|string|Number): `add`, `sub`, `mul`, `div`, `mod`, `pow(int)`, `abs`, `sqrt`, `exp`, `round(int $scale)`, `compare($o)` → -1|0|1.
- Read: `value()` → normalized string, `(string)$n`, `type()` ('integer'|'decimal'), `isIntegerLike()`, `isDecimalLike()`.
- Precision rules:
  - `add/sub/mul/compare/mod`, `pow(n>=0)`: exact.
  - `div`: half-up rounded, base scale 60 (adjusts for magnitude); `pow(n<0)` uses div. `sqrt`/`exp`: rounded to 50 dp.
  - `round` is half-up (away from zero at .5).
- Errors: invalid/INF/NAN input or exponent outside ±10000 → `InvalidArgumentException`; `div`/`mod` by 0, `0^negative` → `DivisionByZeroError`; `mod` needs integer-valued operands, returns Euclidean (non-negative) remainder (`-1 mod 3 = 2`); `sqrt(negative)` → `LogicException`; `pow`/`exp` argument outside ±10000 → `InvalidArgumentException`.
- Float mode: `$n->offPrecision()` returns a float-backed copy (propagates). `backend()` is `@internal` (diagnostics only).

## Precision switch (all modules)
Factories/statics accept trailing `bool $precision = true` (BCMath). `false` → native floats. Use **named arg**: `Normal::of(0, 1, precision: false)`, `Statistics::mean($x, precision: false)`. Applies to: distributions, `ProbabilityMeasure`/`RandomVariable` factories, `Expectation::of`/`Variance::of`, `Statistics::*`. `Vector`/`Matrix`: `->offPrecision()`.

## Probability
- `Probability::of(int|float|string|Number|Probability)` must be in [0,1] else `InvalidArgumentException`. Read: `$p->value()` is a `Number` → string is `$p->value()->value()` (double `value()`).
- `add(P)` (throws if sum > 1), `multiply(P)`, `complement()`, `divide(P)` → returns **`Number`** (not Probability; zero divisor → `DivisionByZeroError`), `compare($x)`, `isUnit()`, `isZero()`.
- `SampleSpace::of(...$outcomes)`: ≥1 outcome, de-duplicated by **strict identity** (`1`, `'1'`, `1.0`, `true` all distinct; objects by `spl_object_id`; NAN/INF rejected). `outcomes()`/`values()`, `contains($o)`, `size()`, `equals()` (order-independent).
- `Event::of($space, ...$outcomes)` (outcomes must be in space), `Event::all($space)`, `Event::empty($space)`; `union`, `intersection`, `difference`, `complement`, `contains`, `isEmpty`, `outcomes`, `sampleSpace`, `equals`. Binary ops require equal sample spaces (`InvalidArgumentException`).
- `ProbabilityMeasure`:
  - `of($space, [w1,w2,...])` positional list, one weight per outcome in space order; weights in [0,1]; sum must be **exactly 1** (exact compare, no tolerance) → use strings (`['0.1','0.2','0.7']`).
  - `fromMap($space, ['H'=>'0.5','T'=>'0.5'])` keyed, OR list of `[outcome, weight]` pairs (needed for objects/arrays or int-vs-string distinctions; PHP array keys coerce `'1'`→`1`).
  - `uniform($space)`: last weight is the remainder so the total is exactly 1.
  - `probabilityFor($outcome)`, `probabilityOf(Event)`, `conditional($a,$b)` (P(B)=0 → `DivisionByZeroError`), `areIndependent($a,$b)`, `sampleSpace()`.
- `RandomVariable::of($space, [v...])` / `fromMap(...)` (same two forms); `mapping()` → `list<Number>`, `valueFor($o)`, `sampleSpace()`.
- `Expectation::of($rv, $measure)->value()` → `Number`; `Variance::of(...)->value()` → population variance `Number`. Rv and measure must share equal sample space.
- `ConditionalProbability::of($a, $b, $measure)->value()` → `Probability`.

## Distribution (all `::of(...)`, validate params → `InvalidArgumentException`)
| Class | Factory | Valid | Kind |
|---|---|---|---|
| `Bernoulli` | `of(p)` | 0≤p≤1 | discrete |
| `Binomial` | `of(n, p)` | n≥0, 0≤p≤1 | discrete |
| `Geometric` | `of(p)` | 0<p≤1; counts trials **including** the first success (support k≥1) | discrete |
| `Poisson` | `of(lambda)` | λ>0 | discrete |
| `Exponential` | `of(rate)` | rate>0 | continuous |
| `Normal` | `of(mean, sd)` | sd>0; `cdf` uses an approximation (Abramowitz–Stegun 7.1.26) | continuous |
| `Uniform` | `of(a, b)` | a<b | continuous |
- All: `expectation()`, `variance()` → `Number`; `cdf(x)` → `Probability`.
- Discrete: `pmf(x)` → `Probability` (non-integer or out-of-support x → mass 0). Continuous: `pdf(x)` → **`Number`** (a density, not a probability).
- Read results: `$d->pmf(2)->value()->value()` (Probability) vs `$d->pdf(1)->value()` (Number).

## Linear
- `Vector::of(...$vals)` (≥1), `zero($dim)`, `basis($dim,$i)`. `add`, `sub`, `negate`, **`scale(Number $s)` requires a `Number`** (not int), `dot`, `cross` (3D only), `norm`, `normSquared`, `normalize` (zero → `LogicException`), `distance`, `isZero`, `isOrthogonalTo`, `isParallelTo`, `dimension`, `get($i)`, `values()` (`Number[]`), `map(fn(Number): mixed)`. Dimension mismatch → `InvalidArgumentException`.
- `Matrix::of([[...],[...]])` (non-empty, rectangular), `zero($r,$c)`, `identity($n)`, `diagonal([..])`. `add`, `sub`, `scale(Number)`, `negate`, `transpose`, `multiply(Matrix)`, `multiplyVector(Vector)`, `determinant` (singular → 0, non-square → `LogicException`), `inverse` (singular → `DivisionByZeroError`), `minor` (1-row matrix → `LogicException`), `cofactor`, `adjugate`, `trace`, `rank`, `shape()` → `[rows, cols]`, `rows()`, `columns()`, `get($r,$c)`, `row($i)`, `column($i)` (→ `Vector`), `diagonalValues()`, `isSquare/isSymmetric/isIdentity/isDiagonal/isSingular`, `equals`.
- Systems: `LinearSystem::of($matrix, $rhsVector)->solve()` returns one of `UniqueSolution` (`->vector()`), `InfiniteSolutions` (`->freeColumns()`, `->reducedMatrix()`), `NoSolution`. Branch with `instanceof`. Also `hasSolution()`, `isConsistent()`. `LinearEquation::of(Vector $coeffs, $const)`: `evaluate`, `normalize`, `scale`, `toRow`.
- Decompositions (method names are uppercase): `LUDecomposition::of($m)` (square; singular → `DivisionByZeroError`) → `L()`, `U()`, `P()`, `solve($rhs)`, `determinant()`. `QRDecomposition::of($m)` (rows ≥ cols, independent columns) → `Q()`, `R()`, `solve($rhs)` (least squares if tall).
- `Eigen::of($m)`: **diagonal matrices only** (else `LogicException`); `values()`, `vectors()`, `characteristicPolynomial()` (`Gauss\Algebra\Polynomial`).
- `LinearMap::fromMatrix($m)`: `apply`, `kernel()`, `image()` (→ `VectorSpace`), `rank`, `nullity`, `isInjective/isSurjective/isBijective`. `VectorSpace::of(...$vecs)` (independent), `zero($dim)`, `basis`, `dimension`, `contains`.
- Exact: add/sub/scale/products. Rounded: norm, normalize, determinant, inverse, LU/QR solve, row reduction.

## Statistics (all static; input `array|Vector`; results `Number` except `count(): int`)
- Descriptive: `count` (empty OK → 0), `sum`, `mean`, `median`, `mode` (tie → first to reach max), `min`, `max`, `range`.
- Spread: `populationVariance`, `sampleVariance` (N−1; needs ≥2), `populationStandardDeviation`, `sampleStandardDeviation`, `meanAbsoluteDeviation`, `medianAbsoluteDeviation` (unscaled), `interquartileRange`, `coefficientOfVariation` (population sd / mean; mean 0 → `DivisionByZeroError`).
- Quantiles (linear interpolation, position `p*(N-1)`): `quantile($data, $p∈[0,1])`, `percentile($data, 0..100)`, `quartile($data, int 0..4)`, `decile($data, int 0..10)`.
- Shape: `moment($data,$order)` (raw), `centralMoment`, `skewness` (population), `kurtosis` (Pearson, **not** excess, not bias-corrected), `excessKurtosis`. Zero variance → `DivisionByZeroError`.
- Pairs: `covariance($x, $y, sample: false)` (default **population**; `sample: true` for N−1), `correlation($x, $y)` (population; zero variance → `DivisionByZeroError`). Lengths must match.
- Matrix (rows = observations, columns = variables): `covarianceMatrix(Matrix, sample: false)`, `correlationMatrix(Matrix)`.
- Weighted: `weightedMean($data,$weights)`, `weightedVariance(...)` (population; weights ≥0, total ≠ 0).
- Empty input (except `count`) → `InvalidArgumentException`.

## Rules when writing code with Gauss
1. Inputs as strings; compute in `Number`; convert to string/float only at the output boundary via `->value()`.
2. Never use `+ - * /` or `round()` on Gauss values. Use `add/sub/mul/div/round`.
3. Compare with `compare()`, not `==`/`===` on strings. Normalized strings can still differ in representation from your literal.
4. Don't claim a result is "exact" if the path includes `div`, `sqrt`, `exp`, norm, inverse, correlation. See precision rules.
5. Matching sample spaces/dimensions/shapes are hard requirements; validate early.
6. For AI-generated model code: check (a) the right primitive was chosen, (b) documented semantics (e.g. `Geometric` counts trials, `kurtosis` isn't excess, `covariance` default is population), (c) tests exist. Add domain tests; Gauss does not make a model correct by itself.

## Lookup (cheapest path)
1. Search this file. 2. `grep -rn "function <name>" src/` and read only that method. 3. Fetch ONE doc page (not README):
`https://github.com/pwaskito611/gauss/blob/master/docs/usage/<name>.md` where `<name>` ∈ `introduction, installation, composition, number, algebra, linear, geometry, statistics, probability, distribution, numerical, optimization, time-series, discrete, precision`; examples under `docs/usage/examples/{basic-statistics,probability-model,hsmm}.md`; design/rules under `docs/technical/{architecture,number,precision,type-system,composability,module-dependencies,mathematical-conventions,numerical-methods,validation,design-decisions}.md`.
(A `raw/refs/heads/master/...` form of each file is linked from its GitHub page and is cheaper than the HTML view.)

## Verify
`php -l <file>`, then `composer test` (or `vendor/bin/phpunit --filter <Name>`). Validate numeric outputs against a hand-computed value in a test.

_Source: README + docs/usage {number, probability, distribution, linear, statistics} on master (Oct 2026). NOT read: algebra, geometry, numerical, optimization, time-series, discrete, precision, technical docs; read those pages before using those modules._
