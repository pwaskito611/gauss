# Module Dependencies

Gauss is organized by domain, but the most important real dependency is its numeric foundation.

## Dependency overview

```text
Number
  ├── Algebra
  ├── Linear
  ├── Probability
  ├── Distribution
  ├── Statistics
  └── Numerical/Optimization
```

## Actual implementation pattern

- `Number` is used throughout arithmetic-heavy modules.
- `Probability` wraps a validated `Number` in `[0, 1]`.
- `Distribution` classes depend on `Number` parameters and return `Probability` values.
- `Vector` and `Matrix` operate over `Number` entries.
- Higher-level modules like statistics and optimization consume numbers, vectors, and matrices generated from those lower layers.

This is a dependency pattern based on actual code structure and not just an idealized conceptual architecture.
