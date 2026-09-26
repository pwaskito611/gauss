# Mathematical Conventions

Gauss defines several conventions in order to keep the library coherent across modules.

## Vector indexing

Vector indices are zero-based and validated against the vector dimension.

## Matrix indexing

Matrix indexing also follows row and column coordinates with validation at access time.

## Probability range

`Probability` values must remain in the closed interval `[0, 1]`.

## Distribution parameters

Distribution classes accept numeric parameters like a Poisson rate, and they enforce domain constraints such as positivity when required.

## Zero and one representation

The library normalizes values so that semantically equivalent zero forms are treated as zero, for example `0`, `0.0`, `0E-10`, and `-0` are considered zero-equivalent.

## Comparison semantics

Comparison is done using `Number::compare()`, which operates on a controlled decimal representation rather than a naïve native float comparison.

## Precision semantics

Precision is defined by the library’s numeric representation and rounding strategy. Some numerical methods may still be approximate by design.
