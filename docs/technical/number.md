# Number

## Purpose

The `Number` class is the central numeric base in Gauss. It is designed to make arithmetic explicit, predictable, and precise enough to support broader mathematical structures in the library.

## Representation

`Number` stores values as normalized decimal strings and relies on BCMath-based helpers for arithmetic and comparisons. This allows the library to avoid silently depending on native floating-point rounding for every operation.

## Precision model

The implementation distinguishes between:

- exact arithmetic on decimal values represented as strings,
- controlled rounding when necessary,
- and approximation in numerical algorithms that are inherently iterative.

This is important because Gauss is not a claim of universal exactness for every method, especially in numerical approximation routines.

## Immutable behavior

`Number` is immutable. Every arithmetic method returns a new `Number` instance rather than mutating the old one. This is a major reason why complex expressions remain predictable and composable.

```php
$left = Number::of('5');
$right = $left->add(Number::of('2'));

// $left remains 5
// $right becomes 7
```

## Arithmetic operations

The core arithmetic methods include:

- `add()`
- `sub()`
- `mul()`
- `div()`
- `mod()`
- `pow()`
- `sqrt()`
- `exp()`
- `round()`

The code uses decimal normalization and controlled scale handling to maintain stable calculations over many operations.

## Comparison

`compare()` is the canonical ordering operation. It compares two `Number` instances without relying on ambiguous float semantics.

## Conversion and validation

The `Number::of()` factory accepts integers, floats, strings, or existing `Number` instances. Invalid numeric values are rejected. The library validates decimal forms before creating a `Number` instance.

## Precision boundaries and limitations

A key design decision is honesty: Gauss does not claim that every operation is mathematically exact in all contexts. Decimal arithmetic is exact for the represented values, but functions like `sqrt()` and `exp()` are still subject to the implementation’s rounding model. Numerical methods may also be approximative by nature.

This is a deliberate design philosophy: Gauss represents the numeric pipeline truthfully rather than overstating guarantee levels.

## Why it matters

`Number` is the foundation for all higher-level mathematical reasoning in the library. Because it is explicit and immutable, it supports algebraic structures, probability values, and linear algebra without relying on implicit hidden conversions.
