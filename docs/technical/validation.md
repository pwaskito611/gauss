# Validation

Validation is a central design concern in Gauss. The library makes correctness more robust by validating inputs and invariants at the point where a mathematical concept is created.

## Validation categories

### Domain validation

Examples include:

- probability values must be between `0` and `1`
- Poisson rate must be positive
- vector dimensions must match when operations require it
- matrix dimensions must be consistent

### Numeric invariants

The library checks that numeric values are represented consistently and reject impossible or malformed values early.

### Boundary and edge cases

Gauss includes tests for:

- zero values
- unit probabilities
- invalid input
- dimension mismatch
- comparison edge cases

### Cross-module checks

Many tests exercise how numeric, probability, and linear modules interact with one another. This is especially important because the library is designed around composition.

## Testing philosophy

The project validates the real behavior of the API rather than only checking isolated mock assumptions. This matches the broader objective of keeping mathematical operations honest and model assembly reliable.
