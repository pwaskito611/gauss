# Architecture

Gauss is organized by mathematical domain rather than by an application framework or a single global model. The architecture mirrors the way mathematicians reason about reusable structures.

## High-level structure

```text
src/
├── Number/
├── Algebra/
├── Linear/
├── Geometry/
├── Probability/
├── Distribution/
├── Statistics/
├── Numerical/
├── Optimization/
├── TimeSeries/
└── Discrete/
```

These are domain namespaces, not a strict stack. A module's directory position does not imply a dependency on the module above or below it.

## Dependency pattern

`Number` is the common numeric dependency, but modules also compose with one another where their APIs require richer structures:

- `Algebra` uses `Number`; `Linear` uses `Number` and `Algebra` for eigenvalue-related operations.
- `Geometry` uses `Number` and `Linear` vectors.
- `Probability` constrains `Number` values to `[0, 1]`; `Distribution` uses both.
- `Statistics` uses `Number`, `Vector`, and `Matrix`.
- `Numerical` uses `Number`; `Optimization` uses `Number` and `Vector`.
- `TimeSeries` uses `Number`, linear-algebra types, and `Statistics`; `Discrete` uses `Number`.

The direct source dependencies are summarized in [Module Dependencies](./module-dependencies.md). This is not a prescribed execution order or a claim that each module depends on every earlier one.

## Design emphasis

- explicit arithmetic
- immutable value objects
- domain-specific validation
- numerical precision awareness
- reusable building blocks for custom models

Gauss is therefore a toolkit and a mathematical composition layer, not a framework abstraction that hides all quantitative reasoning.
