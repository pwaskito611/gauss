# Gauss

A precise and composable mathematical toolkit for PHP, designed to build reliable mathematical models from reusable mathematical primitives.

Gauss combines decimal arithmetic, algebraic structures, probability primitives, linear algebra, and statistical operations in one coherent library. BCMath-backed decimal arithmetic is the default, with native-float arithmetic available explicitly for supported calculations. The intent is not to hide mathematics behind a framework-like abstraction, but to make the mathematical operation explicit in the source code itself.

Gauss supports opt-in native-float calculations through `Number::offPrecision()`
and the `precision: false` argument on supported APIs. BCMath remains the default.
See the [precision mode guide](docs/usage/precision.md) for supported modules,
propagation behavior, and numeric limitations.

## Features

* Decimal-backed numeric core using `Number`
* Explicit arithmetic operations such as `add()`, `sub()`, `mul()`, `div()`, and `compare()`
* Algebraic primitives such as polynomials and monomials
* Linear algebra with vectors, matrices, and system solving
* Probability and distribution primitives for statistical modelling
* Support for numerical and optimization workflows
* Opt-in native-float arithmetic alongside the default BCMath backend
* Composable building blocks suitable for custom domain models
* Explicit mathematical structure that is easier to inspect, test, and validate

## Installation

```bash
composer require pandu/gauss
```

Then load the Composer autoloader:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Number\Number;
```

## Quick Start

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Number\Number;

$x = Number::of('10.5');

$y = Number::of('2.5');

$result = $x
    ->mul($y)
    ->add(Number::of('1'));

echo $result->value();
```

This demonstrates the core Gauss pattern: a precise numeric primitive, explicit mathematics, and a result that can be composed into larger expressions.

## Why Gauss?

### Selectable arithmetic backend

BCMath remains the default. For supported numeric calculations, callers can
explicitly select native-float arithmetic with `Number::offPrecision()` or a
trailing `precision: false` argument on supported APIs:

```php
use Gauss\Number\Number;
use Gauss\Statistics\Statistics;

$floatNumber = Number::of('0.1')->offPrecision();
$floatMean = Statistics::mean([1, 2, 3], precision: false);

echo $floatNumber->add('0.2')->value(); // 0.30000000000000004 (typical)
```

Float mode can be faster for some workloads, but has different rounding,
overflow, and underflow behavior. It is never selected automatically. See the
[precision mode guide](docs/usage/precision.md) for supported APIs, propagation,
and limitations.

### Precision

The numeric foundation is built around `Number`, which defaults to decimal-
string-based arithmetic instead of depending solely on native PHP float
behavior. Supported calculations can opt into float mode explicitly.

### Explicit mathematics

Operations are not hidden behind magic wrappers. Source code reads like mathematics:

```php
$result = $a
    ->mul($b)
    ->sub($c)
    ->div($d);
```

This keeps the computational intent visible and makes individual operations easier to inspect and test.

### Composability

Gauss provides reusable building blocks that can be assembled into larger models:

```text
Number
  ↓
Probability
  ↓
Distribution
  ↓
Vector / Matrix
  ↓
Numerical algorithm
  ↓
Mathematical model
```

Each layer provides explicit building blocks for the next one. This allows larger mathematical models to be constructed without requiring Gauss to provide a specialized abstraction for every possible use case.

### Model building

Gauss is best understood as a toolkit for constructing mathematical models, not merely as a formula collection.

You can combine values, distributions, vectors, matrices, and numerical algorithms into domain-specific workflows while keeping the underlying mathematical operations visible in the source code.

### AI-assisted development and validation

Gauss can also be useful in projects where developers use AI coding assistants.

When an AI generates code that uses Gauss, the developer has an existing mathematical API, implementation, documentation, and test suite against which the generated code can be reviewed.

Instead of validating an AI-generated implementation entirely from scratch, the developer can check:

```text
AI-generated code
       ↓
Gauss primitives used
       ↓
Documented behavior
       ↓
Gauss implementation
       ↓
Existing tests
       ↓
Additional domain-specific tests
       ↓
Developer validation
```

For example, if an AI generates a probability model using `Probability`, `Distribution`, and `Number`, the developer can verify whether:

* the appropriate Gauss primitives were selected;
* the operations are composed correctly;
* the implementation follows the documented semantics;
* the underlying Gauss implementation matches the intended operation;
* existing tests cover the relevant behavior;
* additional tests are required for the specific model.

The important point is that Gauss does not make AI-generated code automatically correct. Mathematical assumptions, model design, implementation, and results still require developer review.

Gauss instead provides a structured and explicit foundation that can reduce how much mathematical implementation the developer has to reconstruct when reviewing AI-generated code.

## Examples

### Example 1 — Basic numerical computation

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Number\Number;

$a = Number::of('12.5');

$b = Number::of('3.5');

$result = $a
    ->mul($b)
    ->sub(Number::of('2'));

echo $result->value();
```

### Example 2 — Probability model

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Distribution\Poisson;
use Gauss\Number\Number;

$lambda = Number::of('2.5');

$poisson = Poisson::of($lambda);

$pmf = $poisson->pmf(3);

echo $pmf->value()->value();
```

### Example 3 — Complex model

The repository includes an HSMM-inspired composition example in the usage documentation:

* [docs/usage/examples/hsmm.md](docs/usage/examples/hsmm.md)

It demonstrates how Gauss primitives can be assembled into a larger probabilistic model without claiming that Gauss ships a built-in HSMM engine or a full production HSMM implementation.

## Documentation

### Getting started

* [docs/usage/introduction.md](docs/usage/introduction.md)
* [docs/usage/installation.md](docs/usage/installation.md)
* [docs/usage/composition.md](docs/usage/composition.md)

### Usage by module

* [docs/usage/number.md](docs/usage/number.md)
* [docs/usage/precision.md](docs/usage/precision.md)
* [docs/usage/algebra.md](docs/usage/algebra.md)
* [docs/usage/linear.md](docs/usage/linear.md)
* [docs/usage/geometry.md](docs/usage/geometry.md)
* [docs/usage/statistics.md](docs/usage/statistics.md)
* [docs/usage/probability.md](docs/usage/probability.md)
* [docs/usage/distribution.md](docs/usage/distribution.md)
* [docs/usage/numerical.md](docs/usage/numerical.md)
* [docs/usage/optimization.md](docs/usage/optimization.md)
* [docs/usage/time-series.md](docs/usage/time-series.md)
* [docs/usage/discrete.md](docs/usage/discrete.md)

### Examples

* [docs/usage/examples/basic-statistics.md](docs/usage/examples/basic-statistics.md)
* [docs/usage/examples/probability-model.md](docs/usage/examples/probability-model.md)
* [docs/usage/examples/hsmm.md](docs/usage/examples/hsmm.md)

### Technical documentation

* [docs/technical/architecture.md](docs/technical/architecture.md)
* [docs/technical/number.md](docs/technical/number.md)
* [docs/technical/precision.md](docs/technical/precision.md)
* [docs/technical/off-precision-audit.md](docs/technical/off-precision-audit.md)
* [docs/technical/type-system.md](docs/technical/type-system.md)
* [docs/technical/composability.md](docs/technical/composability.md)
* [docs/technical/module-dependencies.md](docs/technical/module-dependencies.md)
* [docs/technical/mathematical-conventions.md](docs/technical/mathematical-conventions.md)
* [docs/technical/numerical-methods.md](docs/technical/numerical-methods.md)
* [docs/technical/validation.md](docs/technical/validation.md)
* [docs/technical/design-decisions.md](docs/technical/design-decisions.md)

## Modules

Gauss organizes functionality around mathematical concerns rather than a single monolithic layer:

* `Number` — default BCMath decimal arithmetic and numeric representation
* `Algebra` — polynomials and symbolic expression building blocks
* `Linear` — vectors, matrices, and solvers
* `Probability` — probability values and event-based semantics
* `Distribution` — discrete and continuous distributions
* `Statistics` — summary and relational statistical operations
* `Numerical` — techniques for approximation and numerical computation
* `Optimization` — objective-driven search and constraints
* `TimeSeries` — observation and time-dependent models
* `Discrete` — combinatorics and discrete structures

## Design Philosophy

Gauss follows a small-primitives approach:

1. Use precise numeric primitives.
2. Keep operations explicit and composable.
3. Build larger mathematical structures from smaller units.
4. Make mathematical intent visible in the source code.
5. Keep model construction separate from framework-specific abstractions.
6. Make mathematical implementations easier to inspect, test, and validate.

This is why there is no requirement for a single giant `Hsmm` abstraction before a user can build an HSMM-style model from `Number`, `Probability`, `Distribution`, `Vector`, and `Matrix` building blocks.

The same design is useful when working with AI coding assistants. AI can generate or modify code, while the developer can validate that work against Gauss's existing primitives, documented semantics, implementation, and tests rather than treating the generated implementation as an isolated piece of code.

## Requirements

* PHP 8.2+
* Composer

## Testing

```bash
composer test
```

## License

MIT
