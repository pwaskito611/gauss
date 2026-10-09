# Number

## Purpose

The `Number` class is the central numeric base in Gauss. It is designed to make arithmetic explicit, predictable, and precise enough to support broader mathematical structures in the library.

## Representation

`Number` stores values as normalized decimal strings and uses BCMath-based
helpers by default for arithmetic and comparisons. Calling `offPrecision()`
returns a float-mode copy for native PHP floating-point arithmetic. Scientific-
notation strings are expanded to decimal notation, and equivalent zero forms
normalize to `"0"`. This keeps backend selection explicit rather than silently
switching to floats.

The PHP BCMath extension must be enabled at runtime. `Number::of()` accepts integers, floats, strings, and existing `Number` instances. Floats are converted using their round-trip decimal representation; any floating-point error already present in the value is preserved, so use strings when the decimal literal itself must be represented exactly. Non-finite floats such as `INF` and `NAN` are rejected.

## Precision model

The default BCMath implementation distinguishes between:

- exact addition, subtraction, multiplication, and comparison on represented decimal values,
- rounded division and square-root/exponential results,
- and approximation in numerical algorithms that are inherently iterative.

Float mode is an explicit alternative with native binary floating-point
behavior. The complete API and limitations are documented in
[Precision modes](../usage/precision.md).

`mod()` accepts integer-like operands and uses Euclidean remainders. Negative powers use division, so their results follow the division rounding behavior.

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

In BCMath mode, addition, subtraction, and multiplication preserve all
represented decimal digits. Division chooses a scale based on the operands'
decimal places and relative magnitudes, then rounds half-up; it is not exact for
every quotient. `sqrt()` and `exp()` round to at most 50 fractional decimal
places. `round()` also uses half-up rounding, away from zero at ties. Float
mode instead uses native PHP floats and their finite binary precision.

`pow()` accepts integer exponents from `-10000` through `10000`; `exp()` accepts arguments in `[-10000, 10000]`. Decimal scale used by BCMath-backed operations is guarded at 100,000 places; this is an internal calculation-scale limit, not a limit on numeric magnitude. Scientific-notation input exponents are limited to `[-10000, 10000]`.

## Comparison

`compare()` is the canonical ordering operation. It compares a `Number` with another `Number` or supported scalar input without relying on ambiguous native-float comparison.

## Conversion and validation

The `Number::of()` factory accepts integers, floats, strings, or existing `Number` instances. Invalid numeric values are rejected. The library validates decimal forms before creating a `Number` instance.

## Precision boundaries and limitations

A key design decision is honesty: Gauss does not claim that every operation is mathematically exact in all contexts. Addition, subtraction, multiplication, and comparison are exact for represented decimal values; division, `sqrt()`, and `exp()` follow the implementation's rounding model. Numerical methods may also be approximate by nature.

This is a deliberate design philosophy: Gauss represents the numeric pipeline truthfully rather than overstating guarantee levels.

## Why it matters

`Number` is the foundation for all higher-level mathematical reasoning in the library. Because it is explicit and immutable, it supports algebraic structures, probability values, and linear algebra without relying on implicit hidden conversions.
