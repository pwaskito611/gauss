# Type System

Gauss does not reduce every mathematical concept to a raw PHP scalar. Instead, it models concepts with domain-aware value objects.

## Core concepts

- `Number` for numeric values
- `Probability` for values constrained to `[0, 1]`
- `Vector` for ordered numeric tuples
- `Matrix` for rectangular numeric collections
- `Distribution` objects for probabilistic laws

## Why not just float?

A plain `float` makes it too easy to lose semantic intent. A probability of `0.5` is not just any number; it is a probability. A matrix entry is not just a scalar; it is part of a structured numeric context.

By wrapping concept-specific values, Gauss preserves both mathematical meaning and validation.

```php
$probability = Probability::of('0.5');
```

This is semantically stronger than a bare float because the constructor validates the allowed range.

## Composition

The type system is intentionally layered. Numeric primitives are reused across higher-level ideas, and domain validations ensure the semantics remain coherent as models are assembled.
