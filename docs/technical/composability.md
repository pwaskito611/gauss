# Composability

Gauss is built on the principle that mathematical models are assembled from small reusable primitives. The goal is not to create one giant abstraction for every possible domain object. The goal is to allow users to build the right abstraction for their problem.

## Primitive-first design

```text
small mathematical primitives
        ↓
composition
        ↓
larger mathematical structures
        ↓
domain model
```

Examples of primitives in the project include:

- `Number`
- `Probability`
- `Vector`
- `Matrix`
- `Poisson`
- `Polynomial`

## HSMM as a technical case study

A Hidden Semi-Markov Model can be built without a native `Hsmm` class in the library. A user can compose:

- `Number` for arithmetic
- `Probability` for constrained values
- `Poisson` for emission probabilities
- `Vector` for parameter storage
- `Matrix` for transitions and duration matrices

The model is then assembled using normal application logic. This is the practical expression of Gauss composability.

## Why this matters

This approach keeps the library flexible and keeps user code readable. It also reduces the burden of maintaining a large number of domain-specific built-ins when the real need is a mathematically meaningful set of reusable primitives.
