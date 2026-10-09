# Precision

Gauss separates three different ideas that are often conflated:

## Backend selection

BCMath-backed decimal arithmetic remains the default. `Number::offPrecision()`
returns an immutable copy that uses native PHP float arithmetic. Arithmetic
results inherit float mode from either operand. Gauss value objects that own
numeric state expose the same copy-based conversion where it is meaningful.

Supported static numeric entry points accept a trailing `bool $precision = true`:
`true` keeps BCMath and `false` selects float mode. This is a per-call
choice, not global state. Gauss rebinds managed inputs and intermediates to the
chosen backend, including numeric callback arguments and results at Gauss-owned
algorithm boundaries. Arithmetic performed independently inside user callbacks
is outside Gauss's control.

Integer indices and exact discrete algorithms are deliberately excluded from
float conversion. See the [usage guide](../usage/precision.md) for the API
inventory and examples.

## 1. Exact decimal operations in the default mode

In BCMath mode, `Number` represents values as normalized decimal strings.
Addition, subtraction, multiplication, and comparison operate on those
represented decimal values without converting them to native floats. This does
not make every default-mode `Number` operation exact: division, square root,
and exponential have explicit rounding behavior. In float mode, arithmetic
uses native PHP floats and follows their finite binary precision.

## 2. Scale-bounded decimal arithmetic

The implementation uses BCMath with decimal-string normalization. Addition, subtraction, and multiplication preserve all represented digits; division rounds half-up using an operand- and magnitude-aware scale. `sqrt()` and `exp()` round to at most 50 fractional decimal places. These guarantees apply to the represented input values; a float converted to `Number` may already contain binary floating-point error.

## 3. Numerical approximation

Some operations and algorithms are inherently approximate. This includes division when its exact decimal expansion does not terminate, square roots, exponentials, and iterative methods such as root-finding, optimization, and distribution calculations.

The rounding mode for `Number` division and `round()` is half-up, away from zero on ties. Precision values should therefore be interpreted together with the operation producing them.

## Resource bound

BCMath-backed decimal operations reject a requested or derived calculation scale above 100,000 decimal places with `InvalidArgumentException`. This guards intermediate work; it is not a limit on numeric magnitude, does not silently round scales below the bound, and is not a blanket limit applied by `Number::of()` to input strings.

`Number::pow()` limits integer exponents to `[-10000, 10000]`, and `Number::exp()` limits its argument to `[-10000, 10000]`. The PHP BCMath extension is required at runtime.

## Practical implication

The library should be read as a precision-oriented numerics toolkit, not as a promise that every function is mathematically exact under all circumstances. The method-specific behavior is described in [Number](./number.md).

Float mode is an explicit alternative, not an exact-decimal replacement. It may
be faster for a particular workload, but performance depends on the workload
and environment. The [off-precision audit](./off-precision-audit.md) includes
one local benchmark and measured output differences; those numbers are not
performance guarantees.
