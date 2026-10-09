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

`Number` normalizes equivalent decimal representations, so forms such as `0`, `0.0`, `0E-10`, and `-0` become `"0"`.

## Comparison semantics

`Number::compare()` compares normalized decimal values rather than converting them to native floats. APIs that accept scalar numeric inputs first convert them through `Number`.

## Precision semantics

In the default BCMath mode, addition, subtraction, multiplication, and comparison preserve the represented decimal values. Division, square root, and exponential use controlled rounding, and iterative numerical methods may be approximate by design. Explicit float mode follows native PHP float behavior; see [Precision](./precision.md) for the current limits and rounding model.
