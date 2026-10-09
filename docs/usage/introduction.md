# Introduction

Gauss is a mathematical toolkit for PHP that keeps numerical operations explicit and composable. Instead of forcing users into a single monolithic abstraction, it exposes small mathematical primitives that can be assembled into models for statistics, probability, linear systems, optimization, and other numerical workflows.

The central idea is simple: a user can start from a precise numeric value, compose it with other primitives, and keep the resulting expression readable in code.

## Module view

```text
Number
  ↓
Algebra
  ↓
Linear
  ↓
Geometry

Statistics
  ↓
Probability
  ↓
Distribution

Numerical
  ↓
Optimization
  ↓
TimeSeries
  ↓
Discrete
```

This is a conceptual map, not a strict dependency chain. In practice, Gauss modules are meant to be reusable together when a problem needs them.

## Core philosophy

### 1. Precision first

The numeric base is based on `Number`, which stores values in a string-backed decimal format and delegates arithmetic to carefully controlled decimal operations.
BCMath is the default; supported computations can explicitly opt into native
float mode. See [Precision modes](precision.md) for the API and its limitations.

### 2. Explicit mathematics

Operations are expressed directly in code:

```php
$result = $x
    ->mul($y)
    ->add(Number::of('1'))
    ->div(3);
```

The source code states the mathematics in a way that is readable to both humans and other developers.

### 3. Composability

A single primitive, such as a probability value, can be used in a broader statistical or distribution workflow. A vector or matrix can then be combined with probabilities, rates, or numerical methods to form a larger model.

## When to use which module

- Use `Number` for default BCMath decimal arithmetic or integer arithmetic.
- Use `Algebra` for symbolic polynomial operations.
- Use `Linear` for vectors, matrices, and linear systems.
- Use `Probability` for probability values and event logic.
- Use `Distribution` for distributions such as Poisson or Bernoulli.
- Use `Statistics` for summary and relationship calculations.
- Use `Numerical` and `Optimization` for approximation and constrained search.
- Use `TimeSeries` and `Discrete` for time-dependent or discrete mathematical models.

## Recommended reading order

1. [installation.md](installation.md)
2. [number.md](number.md)
3. [probability.md](probability.md)
4. [distribution.md](distribution.md)
5. [linear.md](linear.md)
6. [composition.md](composition.md)
7. [examples/basic-statistics.md](examples/basic-statistics.md)
8. [examples/hsmm.md](examples/hsmm.md)
