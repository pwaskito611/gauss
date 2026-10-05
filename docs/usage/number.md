# Number

## Overview

`Gauss\Number\Number` is Gauss's immutable decimal value type. Values are
stored as normalized decimal strings. Arithmetic operates on those decimal
representations rather than using PHP floats for calculations.

Use numeric strings when the original decimal value must be preserved. A float
may already contain binary floating-point error before it is passed to Gauss.

## Construction

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Number\Number;

$integer = Number::of(2);
$decimal = Number::of('10.5');
$scientific = Number::of('1.25e-3');
$one = Number::of(0)->one();

echo $decimal->value(); // 10.5
```

`Number::of()` accepts an integer, finite float, numeric string, or existing
`Number`. Strings may use decimal or scientific notation. Input is normalized:
for example, `'001.2300'` becomes `'1.23'`, and zero is represented as `'0'`.
Passing an existing `Number` returns the same instance, not a clone:

```php
$original = Number::of('1.23');
$same = Number::of($original);

var_dump($same === $original); // true
```

Invalid numeric strings, non-finite values such as `INF` and `NAN`, and
scientific-notation exponents outside `-10000` to `10000` are rejected with
`InvalidArgumentException`. A float's pre-existing precision error cannot be
recovered during conversion; prefer strings for decimal literals that must be
preserved exactly.

The instance method `one()` returns the multiplicative identity `1`.

## Mathematical constants

```php
$pi = Number::pi();
$e = Number::e();
$one = Number::of(0)->one();
```

`pi()` and `e()` return fixed decimal constants represented to 50 decimal
places. They are decimal approximations of the mathematical constants, not
exact values. `one()` returns the exact integer value `1`.

## Arithmetic

```php
$x = Number::of('10.5');
$y = Number::of('2.5');

$sum = $x->add($y);      // 13
$difference = $x->sub($y); // 8
$product = $x->mul($y);  // 26.25
$quotient = $x->div($y); // 4.2
```

`add()`, `sub()`, and `mul()` perform exact decimal arithmetic on the stored
decimal values. The methods accept an integer, float, numeric string, or
`Number` as their operand. As with construction, a float operand may reflect
precision loss that occurred before the call.

### Division

`div()` rejects a zero divisor with `DivisionByZeroError`. Unlike
`add()`, `sub()`, and `mul()`, division generally has a rounded decimal
result: it uses half-up rounding and normalizes trailing zeroes. For example,
`1 / 3` is represented with 60 repeating decimal digits:

```php
$third = Number::of(1)->div(3);

echo $third->value();
// 0.333333333333333333333333333333333333333333333333333333333333
```

Division chooses its working scale based on operand magnitudes and decimal
scales. This magnitude-aware scale allows very small quotients to retain
significant digits instead of being truncated to zero:

```php
$small = Number::of(1)->div('1e100');

echo $small->value();
// 0. followed by 99 zeroes and 1
```

The number of leading zeroes in that output is part of the normalized decimal
representation; division is still rounded to a finite scale.

### Euclidean modulo

`mod()` accepts integer-valued operands only and returns a non-negative
remainder in the range `[0, |divisor|)`. The result follows Euclidean modulo,
so negative dividends do not produce a negative remainder:

```php
Number::of(10)->mod(3)->value();  // "1"
Number::of(-1)->mod(3)->value(); // "2"
```

Thus `-1 mod 3` is `2`, not `-1`. A non-integer operand throws
`InvalidArgumentException`; a zero divisor throws `DivisionByZeroError`.

### Integer powers

`pow()` accepts an integer exponent from `-10000` through `10000`, inclusive:

```php
Number::of(2)->pow(10)->value(); // "1024"
Number::of(2)->pow(-2)->value(); // "0.25"
```

Non-negative exponents use exact multiplication of the stored decimal value.
Negative exponents calculate a reciprocal using `div()`, so their results
follow division's rounding behavior. Exponents outside the supported range,
including `PHP_INT_MIN`, throw `InvalidArgumentException`. An exponent of zero
returns `1`, including for a zero base.

A negative exponent for a zero base attempts division by zero and throws
`DivisionByZeroError`.

## Approximate mathematical operations

### Square root

`sqrt()` accepts non-negative values. Its result is rounded half-up to the
library's 50-decimal-place scale and normalized:

```php
Number::of(2)->sqrt()->value();
// "1.41421356237309504880168872420969807856967187537695"
```

The result is a deterministic decimal approximation, not an exact square
root. A negative input throws `LogicException`.

### Exponential

`exp()` computes the exponential function and rounds the result half-up to 50
decimal places, then normalizes it. The result is a deterministic decimal
approximation, not an exact value:

```php
Number::of(1)->exp()->value();
// "2.71828182845904523536028747135266249775724709369996"
```

The argument must be in the inclusive range `-10000` through `10000`;
otherwise `InvalidArgumentException` is thrown. For arguments at or below
`-130`, the implementation returns zero directly because the result rounds to
zero at the supported output scale:

```php
Number::of(-130)->exp()->value(); // "0"
```

Some arguments greater than `-130` can also round to zero.

## Rounding

`round(int $scale)` rounds to the requested number of decimal places using
half-up rounding. At an exact half, the magnitude rounds away from zero; the
result is then normalized, so trailing zeroes are not retained:

```php
Number::of('2.5')->round(0)->value();   // "3"
Number::of('-2.5')->round(0)->value();  // "-3"
Number::of('2.345')->round(2)->value(); // "2.35"
```

The scale must be non-negative and within the internal supported scale;
otherwise `InvalidArgumentException` is thrown.

## Inspection and conversion

```php
$integer = Number::of('123.00');
$decimal = Number::of('123.45');

$integer->value();          // "123"
$integer->type();           // "integer"
$integer->isIntegerLike();  // true
$integer->isDecimalLike();  // false
$decimal->type();           // "decimal"
$decimal->isDecimalLike();  // true
```

- `value()` returns the normalized numeric string.
- `type()` returns either `"integer"` or `"decimal"` based on that string.
- `isIntegerLike()` is true when the normalized string has no decimal point.
- `isDecimalLike()` is true when the normalized string has a decimal point.
- `__toString()` returns the same normalized representation.
- `compare($other)` returns `-1`, `0`, or `1` and compares decimal values
  without rounding them to the square-root/exponential scale.
- `abs()` returns the non-negative magnitude. For an already non-negative
  value it may return the same immutable instance.

```php
echo Number::of('3.14'); // 3.14
```

## Zero handling

Zero detection uses the normalized decimal representation, not a fixed-scale
comparison threshold. Inputs such as `0`, `0.0`, and `-0` normalize to `"0"`.
A non-zero value is not treated as zero merely because it is smaller than a
particular operation's output scale:

```php
Number::of('1e-100')->compare(0); // 1
Number::of('1e-100')->value();    // "0." followed by 99 zeroes and 1
```

Operation-specific rounding can still produce zero; for example, sufficiently
small results from `sqrt()` or `exp()` can round to zero at their output
scale.

## Precision model

| Operation | Precision behavior |
| --- | --- |
| `add()`, `sub()`, `mul()` | Exact decimal arithmetic on normalized stored values |
| `compare()` | Exact comparison; no fixed-scale rounding |
| `mod()` | Exact Euclidean remainder for integer operands |
| `pow()` with exponent `>= 0` | Exact multiplication; exponent zero returns `1` |
| `pow()` with exponent `< 0` | Reciprocal computed using rounded division |
| `div()` | Deterministic half-up rounded result; working scale adjusts to operand magnitude and decimal scale |
| `sqrt()` | Deterministic half-up rounded result at 50 decimal places |
| `exp()` | Deterministic half-up rounded result at 50 decimal places |
| `round()` | Half-up rounding to the requested scale |

Exact arithmetic refers to the stored decimal values. It does not undo
precision loss from a float before it was converted to `Number`. Division has
a dynamic working scale with a 60-place base; very small results may therefore
contain many leading fractional zeroes while retaining significant digits.

## Limits

The following are private implementation constants, not public API members.
They describe current behavior:

| Implementation limit | Behavior |
| --- | --- |
| `MAX_EXPONENT = 10000` | Maximum absolute integer exponent accepted by `pow()` |
| `MAX_EXP_ARGUMENT = 10000` | Maximum absolute argument accepted by `exp()` |
| `NEGATIVE_EXP_ZERO_BOUNDARY = 130` | Arguments at or below `-130` return zero directly |
| `SCALE = 50` | Output scale used for rounded `sqrt()` and `exp()` results |
| `INTERNAL_SCALE = 60` | Baseline working scale used by `div()` before magnitude/operand-scale adjustments |

`Number::of()` also rejects scientific-notation exponents outside `-10000` to
`10000`. Internal decimal calculations have a maximum supported scale; an
operation that exceeds it may throw `InvalidArgumentException`.

## Exceptions

| Method / operation | Exception | Condition |
| --- | --- | --- |
| `of()` | `InvalidArgumentException` | Invalid numeric string, non-finite float, or unsupported scientific-notation exponent |
| `div()` | `DivisionByZeroError` | Divisor is zero |
| `mod()` | `InvalidArgumentException` | Either operand is not integer-valued |
| `mod()` | `DivisionByZeroError` | Divisor is zero |
| `pow()` | `InvalidArgumentException` | Exponent is outside `-10000` to `10000` |
| `pow()` | `DivisionByZeroError` | Zero base with a negative exponent |
| `sqrt()` | `LogicException` | Input is negative |
| `exp()` | `InvalidArgumentException` | Argument is outside `-10000` to `10000`, or rounding cannot be certified within internal limits |
| `round()` | `InvalidArgumentException` | Scale is negative or exceeds the internal supported scale |
| Arithmetic / comparison | `InvalidArgumentException` | An internal decimal scale exceeds the supported maximum |

## Internal Decimal helper

`Decimal` is an internal BCMath-based helper used by `Number`; it is marked
`@internal` and is not part of the public API. It handles numeric validation
and normalization, float conversion, zero detection, arithmetic, comparison,
scale and order-of-magnitude utilities, and rounding. Its internals are
mentioned here only to explain the implementation and potential stack traces.

## Related modules

- [algebra.md](algebra.md)
- [linear.md](linear.md)
- [statistics.md](statistics.md)
- [probability.md](probability.md)
- [distribution.md](distribution.md)
