# Discrete

## Overview

The discrete module focuses on finite and countable mathematical objects: sets, relations, sequences, combinatorial functions, and number-theoretic utilities. These APIs are intentionally small and composable, and they rely on `Gauss\Number\Number` for exact arithmetic on integer-like values.

The module provides:

- sets and binary relations with `Set` and `Relation`;
- arithmetic, geometric, and recurrence-based sequences;
- combinatorial coefficients with `Factorial`, `Permutation`, `Combination`, and `Multinomial`;
- divisibility, gcd, lcm, congruence, and modular arithmetic;
- factorization, divisor functions, Euler's totient, primality testing, and integer square roots.

Values are converted through `Number`. Inputs are validated to be integer-like where the operation requires exact integer semantics. Operations that divide or take square roots inherit `Number`'s rounded decimal behavior, but integer-domain operations such as divisibility, gcd, and modular reduction are exact under `Number`'s comparison rules.

## Core types

| Type | Purpose |
| --- | --- |
| `Gauss\Discrete\Set\Set` | Immutable set of `Number` values with duplicate normalization. |
| `Gauss\Discrete\Set\Relation` | Immutable binary relation over `Number` pairs. |
| `Gauss\Discrete\Sequence\ArithmeticSequence` | Arithmetic sequence defined by first term and common difference. |
| `Gauss\Discrete\Sequence\GeometricSequence` | Geometric sequence defined by first term and common ratio. |
| `Gauss\Discrete\Sequence\Recurrence` | Second-order recurrence defined by two initial values and a rule. |
| `Gauss\Discrete\Combinatorics\Factorial` | `n!` for non-negative integers. |
| `Gauss\Discrete\Combinatorics\Permutation` | `nPr` for `0 <= r <= n`. |
| `Gauss\Discrete\Combinatorics\Combination` | `nCr` for `0 <= r <= n`. |
| `Gauss\Discrete\Combinatorics\Multinomial` | Multinomial coefficient for a list of parts. |
| `Gauss\Discrete\NumberTheory\Divisibility` | Divisibility test, quotient, and remainder. |
| `Gauss\Discrete\NumberTheory\GCD` | Greatest common divisor. |
| `Gauss\Discrete\NumberTheory\LCM` | Least common multiple. |
| `Gauss\Discrete\NumberTheory\ExtendedGCD` | Bézout coefficients of `a` and `b`. |
| `Gauss\Discrete\NumberTheory\Coprime` | Coprimality test. |
| `Gauss\Discrete\NumberTheory\Congruence` | Congruence test modulo `m`. |
| `Gauss\Discrete\NumberTheory\ModularArithmetic` | Modular addition, subtraction, multiplication, and power. |
| `Gauss\Discrete\NumberTheory\ModularInverse` | Modular multiplicative inverse. |
| `Gauss\Discrete\NumberTheory\Factorization` | Prime factorization with exponents. |
| `Gauss\Discrete\NumberTheory\DivisorFunctions` | Divisor count `tau(n)` and divisor sum `sigma(n)`. |
| `Gauss\Discrete\NumberTheory\EulerTotient` | Euler's totient function. |
| `Gauss\Discrete\NumberTheory\IntegerSquareRoot` | Floor of the integer square root. |
| `Gauss\Discrete\NumberTheory\Prime` | Primality test and next-prime search. |

## Input model

Discrete APIs accept and return `Gauss\Number\Number` values. Integers are validated using the canonical decimal string form of the `Number`, so values must be exact integers such as `3`, `-7`, or `"42"`. Non-integer values such as `1.5` are rejected where the operation requires an integer.

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Number\Number;

Number::of(5);   // valid integer input
Number::of(-3);  // valid integer input
Number::of('7'); // valid integer input
```

Prefer strings when preserving a decimal literal matters, since an input float may already contain binary floating-point error.

## Sets

### `Set`

`Set::of()` normalizes duplicates by comparing values with `Number::compare()`. The result is an immutable set with set-like operations.

```php
public static function of(array $values): self;

public function contains(Number $value): bool;
public function count(): int;
public function equals(self $other): bool;
public function isSubsetOf(self $other): bool;
public function isProperSubsetOf(self $other): bool;
public function isDisjointFrom(self $other): bool;
public function union(self $other): self;
public function intersection(self $other): self;
public function difference(self $other): self;
```

`equals()` checks mutual subset inclusion. `isProperSubsetOf()` excludes the equality case. `isDisjointFrom()` returns `true` when no shared value exists.

```php
<?php

use Gauss\Discrete\Set\Set;
use Gauss\Number\Number;

$numbers = Set::of([
    Number::of(1),
    Number::of(2),
    Number::of(2),
    Number::of(3),
]);

$numbers->contains(Number::of(2)); // true
$numbers->count();                 // 3
```

### `Relation`

`Relation::of()` normalizes duplicate pairs and exposes basic operations over a binary relation.

```php
public static function of(array $pairs): self;

public function contains(array $pair): bool;
public function count(): int;
public function domain(): array;    // list<Number>
public function range(): array;     // list<Number>
public function inverse(): self;
public function compose(self $other): self;
```

Each pair is an array of two `Number` values: `[left, right]`.

`domain()` returns the distinct left elements; `range()` returns the distinct right elements.

`inverse()` returns the relation with each pair swapped.

`compose()` uses the established contract “other after this”. For every pair `(a, b)` in this relation and `(b, c)` in the other relation, the composed relation contains `(a, c)`.

```php
<?php

use Gauss\Discrete\Set\Relation;
use Gauss\Number\Number;

$relation = Relation::of([
    [Number::of(1), Number::of(2)],
    [Number::of(2), Number::of(3)],
]);

$relation->contains([Number::of(1), Number::of(2)]); // true
$relation->domain();                                  // [1, 2]
$relation->range();                                   // [2, 3]
```

## Sequences

Arithmetic, geometric, and recurrence sequences contain numeric values and can
opt into float mode with `offPrecision()`. Sequence indices remain integer
validated:

```php
use Gauss\Discrete\Sequence\ArithmeticSequence;
use Gauss\Number\Number;

$sequence = ArithmeticSequence::from(Number::of(2), Number::of(3));
$term = $sequence->offPrecision()->at(Number::of(5));

$term->value();   // "14"
$term->backend(); // "float"
```

Combinatorics and number-theory routines retain exact integer semantics and are
not switched to floats. Recurrence callbacks receive float-mode terms when the
sequence is converted; arithmetic the callback starts independently is still
the callback author's responsibility. See [Precision modes](precision.md).

### `ArithmeticSequence`

Defined by a first term and a common difference.

```php
public static function from(Number $first, Number $difference): self;

public function first(): Number;
public function at(Number $n): Number;
```

`at()` uses a one-based index. The index must be at least `1`; otherwise `InvalidArgumentException` is thrown.

```php
<?php

use Gauss\Discrete\Sequence\ArithmeticSequence;
use Gauss\Number\Number;

$sequence = ArithmeticSequence::from(Number::of(3), Number::of(2));

$sequence->first()->value();         // "3"
$sequence->at(Number::of(5))->value(); // "11"
```

### `GeometricSequence`

Defined by a first term and a common ratio.

```php
public static function from(Number $first, Number $ratio): self;

public function first(): Number;
public function at(Number $n): Number;
```

`at()` uses a one-based integer index. The index must be a non-negative integer and at least `1`. Indices larger than `10000` are rejected to keep the exponent within the supported `Number::pow()` range; otherwise `InvalidArgumentException` is thrown.

```php
<?php

use Gauss\Discrete\Sequence\GeometricSequence;
use Gauss\Number\Number;

$sequence = GeometricSequence::from(Number::of(2), Number::of(3));

$sequence->first()->value();            // "2"
$sequence->at(Number::of(4))->value(); // "54"
```

### `Recurrence`

Models a second-order recurrence defined by two initial values and a rule.

```php
public static function of(array $initialValues, callable $rule): self;

public function first(): Number;
public function at(Number $n): Number;
```

`$initialValues` must contain exactly two `Number` values. The `$rule` receives the previous two values and returns the next `Number`: `fn(Number $previous, Number $previousPrevious): Number`.

`at()` uses a one-based integer index. Indices `1` and `2` return the initial values directly. Higher indices are computed iteratively; the rule is applied repeatedly until the requested index is reached. Indices must be integers and at least `1`; otherwise `InvalidArgumentException` is thrown.

```php
<?php

use Gauss\Discrete\Sequence\Recurrence;
use Gauss\Number\Number;

$fibonacci = Recurrence::of(
    [Number::of(0), Number::of(1)],
    static fn (Number $a, Number $b): Number => $a->add($b),
);

$fibonacci->at(Number::of(1))->value();  // "0"
$fibonacci->at(Number::of(8))->value();  // "13"
```

## Combinatorics

### `Factorial`

```php
public static function of(Number $n): Number;
```

Returns `n!`. `n` must be a non-negative integer; otherwise `InvalidArgumentException` is thrown. `0!` and `1!` both return `1`.

### `Permutation`

```php
public static function of(Number $n, Number $r): Number;
```

Returns `nPr`. Both inputs must be non-negative integers with `0 <= r <= n`; otherwise `InvalidArgumentException` is thrown.

### `Combination`

```php
public static function of(Number $n, Number $r): Number;
```

Returns `nCr`. Both inputs must be non-negative integers with `0 <= r <= n`; otherwise `InvalidArgumentException` is thrown. Internally the smaller of `r` and `n - r` is used to reduce the number of multiplications.

### `Multinomial`

```php
public static function of(Number $n, array $parts): Number;
```

Returns the multinomial coefficient `n! / (k1! * k2! * ...)`. `n` must be a non-negative integer. `$parts` must be a non-empty list of non-negative integers whose sum equals `n`. All violations throw `InvalidArgumentException`.

```php
<?php

use Gauss\Discrete\Combinatorics\Combination;
use Gauss\Discrete\Combinatorics\Factorial;
use Gauss\Discrete\Combinatorics\Multinomial;
use Gauss\Discrete\Combinatorics\Permutation;
use Gauss\Number\Number;

Factorial::of(Number::of(5))->value();                        // "120"
Permutation::of(Number::of(5), Number::of(2))->value();       // "20"
Combination::of(Number::of(5), Number::of(2))->value();       // "10"
Multinomial::of(Number::of(5), [
    Number::of(2),
    Number::of(2),
    Number::of(1),
])->value();                                                  // "30"
```

## Number theory

### Divisibility

```php
public static function isDivisibleBy(Number $a, Number $b): bool;
public static function quotient(Number $a, Number $b): Number;
public static function remainder(Number $a, Number $b): Number;
```

`quotient()` returns the truncated quotient toward zero. `remainder()` returns a non-negative remainder adjusted for sign. Both inputs must be integers. A zero divisor throws `DivisionByZeroError`.

```php
<?php

use Gauss\Discrete\NumberTheory\Divisibility;
use Gauss\Number\Number;

Divisibility::isDivisibleBy(Number::of(12), Number::of(4)); // true
Divisibility::quotient(Number::of(13), Number::of(4))->value(); // "3"
Divisibility::remainder(Number::of(13), Number::of(4))->value(); // "1"
```

### `GCD`

```php
public static function of(Number $a, Number $b): Number;
```

Returns the greatest common divisor of `a` and `b` using the Euclidean algorithm. Both inputs must be integers. `GCD(0, 0)` returns `0`.

### `LCM`

```php
public static function of(Number $a, Number $b): Number;
```

Returns the least common multiple of `a` and `b`. Both inputs must be integers. `LCM(a, 0)` and `LCM(0, b)` return `0`.

### `ExtendedGCD`

```php
public static function of(Number $a, Number $b): self;

public function gcd(): Number;
public function coefficientX(): Number;
public function coefficientY(): Number;
```

Returns Bézout coefficients `x` and `y` such that `a*x + b*y = gcd(a, b)`. Both inputs must be integers. `ExtendedGCD(0, 0)` returns gcd `0` with both coefficients `0`.

```php
<?php

use Gauss\Discrete\NumberTheory\ExtendedGCD;
use Gauss\Number\Number;

$bezout = ExtendedGCD::of(Number::of(240), Number::of(46));

$bezout->gcd()->value();          // "2"
$bezout->coefficientX()->value(); // "-9"
$bezout->coefficientY()->value(); // "47"
```

### `Coprime`

```php
public static function of(Number $a, Number $b): bool;
```

Returns `true` when `gcd(|a|, |b|) = 1`. Both inputs must be integers.

### `Congruence`

```php
public static function of(Number $a, Number $b, Number $modulus): bool;
```

Returns `true` when `a ≡ b (mod modulus)`. All inputs must be integers and the modulus must be positive; otherwise `InvalidArgumentException` is thrown.

### `ModularArithmetic`

```php
public static function add(Number $a, Number $b, Number $modulus): Number;
public static function subtract(Number $a, Number $b, Number $modulus): Number;
public static function multiply(Number $a, Number $b, Number $modulus): Number;
public static function power(Number $a, Number $exponent, Number $modulus): Number;
```

All operands must be integers and the modulus must be positive. The exponent for `power()` must additionally be non-negative. Results are normalized into the interval `[0, modulus)`.

`power()` uses square-and-multiply and reduces intermediate products modulo `modulus` at every step.

### `ModularInverse`

```php
public static function of(Number $a, Number $modulus): Number;
```

Returns the multiplicative inverse of `a` modulo `modulus`. Both inputs must be integers and the modulus must be positive. When the modulus is `1`, the method returns `0`. When `a` and `modulus` are not coprime, the inverse does not exist and `InvalidArgumentException` is thrown.

### `Factorization`

```php
public static function of(Number $n): self;

public function primeFactors(): array; // list<Number>
public function exponents(): array;    // list<Number>
```

Returns the prime factorization of `n` with exponents. `n` must be a positive integer. `Factorization(1)` returns empty factor and exponent lists. Negative values and zero throw `InvalidArgumentException`.

### `DivisorFunctions`

```php
public static function tau(Number $n): Number;
public static function sigma(Number $n): Number;
```

`tau(n)` returns the number of positive divisors. `sigma(n)` returns the sum of positive divisors. Both require `n >= 1`.

### `EulerTotient`

```php
public static function of(Number $n): Number;
```

Returns Euler's totient `phi(n)`, the count of integers in `[1, n]` coprime to `n`. `n` must be a positive integer. `phi(1)` returns `1`.

### `IntegerSquareRoot`

```php
public static function of(Number $n): Number;
```

Returns `floor(sqrt(n))` using integer binary search. `n` must be a non-negative integer. `IntegerSquareRoot(0)` and `IntegerSquareRoot(1)` return the input itself.

### `Prime`

```php
public static function isPrime(Number $n): bool;
public static function nextPrime(Number $n): Number;
```

`isPrime()` returns `true` only for integers greater than or equal to `2` that have no divisors other than `1` and themselves. Values smaller than `2` return `false`.

`nextPrime()` returns the smallest prime strictly greater than `n`. For `n < 2`, the method returns `2`.

```php
<?php

use Gauss\Discrete\NumberTheory\DivisorFunctions;
use Gauss\Discrete\NumberTheory\EulerTotient;
use Gauss\Discrete\NumberTheory\Factorization;
use Gauss\Discrete\NumberTheory\GCD;
use Gauss\Discrete\NumberTheory\LCM;
use Gauss\Discrete\NumberTheory\ModularArithmetic;
use Gauss\Discrete\NumberTheory\ModularInverse;
use Gauss\Discrete\NumberTheory\Prime;
use Gauss\Number\Number;

GCD::of(Number::of(48), Number::of(18))->value();              // "6"
LCM::of(Number::of(4), Number::of(6))->value();               // "12"
EulerTotient::of(Number::of(9))->value();                     // "6"
DivisorFunctions::tau(Number::of(12))->value();               // "6"
DivisorFunctions::sigma(Number::of(12))->value();             // "28"
Prime::isPrime(Number::of(13));                               // true
Prime::nextPrime(Number::of(13))->value();                    // "17"
ModularArithmetic::power(Number::of(2), Number::of(10), Number::of(1000))->value(); // "24"
ModularInverse::of(Number::of(3), Number::of(11))->value();   // "4"

$factorization = Factorization::of(Number::of(360));
$factorization->primeFactors(); // [2, 3, 5]
$factorization->exponents();    // [3, 2, 1]
```

## Precision model

Discrete calculations use `Number` for numeric conversion, arithmetic, comparison, and modular reduction. Integer-domain operations such as divisibility, gcd, modular arithmetic, and factorization are exact under `Number`'s comparison rules, because every intermediate value stays an exact integer. Float mode is only relevant to the numeric sequence-value objects described above; it does not alter combinatorics, number theory, set operations, or index validation.

Operations that divide, take square roots, or iterate over a heuristic range may inherit `Number`'s rounded decimal behavior:

| Routine / operation | Precision behavior |
| --- | --- |
| `Set`, `Relation` | Exact `Number::compare()` for membership and equality; no arithmetic. |
| `ArithmeticSequence` | BCMath decimal addition and multiplication by default; `offPrecision()` selects float for sequence values. |
| `GeometricSequence` | Uses `Number::pow()` for the exponent; sequence values can be switched to float with `offPrecision()`. |
| `Recurrence` | Uses the supplied rule; float mode passes float-mode terms and rebinds results, while independent calculations inside the rule remain caller-controlled. |
| `Factorial`, `Permutation`, `Combination`, `Multinomial` | Exact decimal multiplication; `Combination` and `Multinomial` use rounded division. |
| `Divisibility::quotient()` | Uses `bcdiv(..., 0)` on absolute decimal strings, so the truncation is exact for integer inputs. |
| `Divisibility::remainder()` | Exact subtraction after truncation. |
| `GCD`, `LCM`, `ExtendedGCD` | Exact integer operations; `LCM` uses one rounded division. |
| `Congruence` | Delegates to `Divisibility` and `GCD`; exact for integers. |
| `ModularArithmetic` | Exact integer addition, subtraction, multiplication, and square-and-multiply; `normalize()` reduces with `Divisibility::remainder()`. |
| `ModularInverse` | Uses `ExtendedGCD`; exact for integer inputs. |
| `Factorization` | Exact trial division; only divides exact integers. |
| `DivisorFunctions` | `tau()` uses exact integer multiplication; `sigma()` uses `Number::pow()` and rounded division by `factor - 1`. |
| `EulerTotient` | Uses `Number::div()` during the reduction step, which follows rounded division. |
| `IntegerSquareRoot` | Uses `Number::div(2)` inside the binary search; values remain integer because `mid` is constructed to be even. |
| `Prime` | Uses `IntegerSquareRoot::of()` and `Number::mod()`; exact for integer inputs. |

`div()` uses Gauss's rounded decimal division; `sqrt()` uses half-up rounding to 50 decimal places. Statistical calculations that involve either operation are deterministic under those rules, but should not be described as universally exact.

## Exceptions and edge cases

| API | Exception | Condition |
| --- | --- | --- |
| `Combination::of()` | `InvalidArgumentException` | `n` or `r` is not an integer, is negative, or `r > n`. |
| `Factorial::of()` | `InvalidArgumentException` | `n` is not an integer or is negative. |
| `Permutation::of()` | `InvalidArgumentException` | `n` or `r` is not an integer, is negative, or `r > n`. |
| `Multinomial::of()` | `InvalidArgumentException` | `n` is not a non-negative integer, `$parts` is empty, a part is not a non-negative integer, or parts do not sum to `n`. |
| `ArithmeticSequence::at()` | `InvalidArgumentException` | Index is less than `1`. |
| `GeometricSequence::at()` | `InvalidArgumentException` | Index is not an integer, is less than `1`, or exceeds `10000`. |
| `Recurrence::of()` | `InvalidArgumentException` | Initial value count is not exactly two. |
| `Recurrence::at()` | `InvalidArgumentException` | Index is not an integer or is less than `1`. |
| `Divisibility::isDivisibleBy()` / `quotient()` / `remainder()` | `InvalidArgumentException` | Either operand is not an integer. |
| `Divisibility::isDivisibleBy()` / `quotient()` / `remainder()` | `DivisionByZeroError` | Divisor is zero. |
| `GCD::of()` | `InvalidArgumentException` | Either operand is not an integer. |
| `LCM::of()` | `InvalidArgumentException` | Either operand is not an integer. |
| `ExtendedGCD::of()` | `InvalidArgumentException` | Either operand is not an integer. |
| `Coprime::of()` | `InvalidArgumentException` | Either operand is not an integer. |
| `Congruence::of()` | `InvalidArgumentException` | Any operand is not an integer or the modulus is not positive. |
| `ModularArithmetic::*` | `InvalidArgumentException` | Any operand is not an integer, the modulus is not positive, or the exponent is negative for `power()`. |
| `ModularInverse::of()` | `InvalidArgumentException` | Inputs are not integers, the modulus is not positive, or the inputs are not coprime. |
| `Factorization::of()` | `InvalidArgumentException` | `n` is not an integer, is negative, or is zero. |
| `DivisorFunctions::tau()` / `sigma()` | `InvalidArgumentException` | `n` is not an integer or is less than `1`. |
| `EulerTotient::of()` | `InvalidArgumentException` | `n` is not an integer or is less than `1`. |
| `IntegerSquareRoot::of()` | `InvalidArgumentException` | `n` is not an integer or is negative. |
| `Prime::isPrime()` / `nextPrime()` | `InvalidArgumentException` | `n` is not an integer. |

## Complete example

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Discrete\Combinatorics\Combination;
use Gauss\Discrete\Combinatorics\Factorial;
use Gauss\Discrete\Combinatorics\Multinomial;
use Gauss\Discrete\Combinatorics\Permutation;
use Gauss\Discrete\NumberTheory\DivisorFunctions;
use Gauss\Discrete\NumberTheory\EulerTotient;
use Gauss\Discrete\NumberTheory\Factorization;
use Gauss\Discrete\NumberTheory\GCD;
use Gauss\Discrete\NumberTheory\LCM;
use Gauss\Discrete\NumberTheory\ModularArithmetic;
use Gauss\Discrete\NumberTheory\ModularInverse;
use Gauss\Discrete\NumberTheory\Prime;
use Gauss\Discrete\Sequence\ArithmeticSequence;
use Gauss\Discrete\Sequence\GeometricSequence;
use Gauss\Discrete\Sequence\Recurrence;
use Gauss\Discrete\Set\Relation;
use Gauss\Discrete\Set\Set;
use Gauss\Number\Number;

$setA = Set::of([Number::of(1), Number::of(2), Number::of(3)]);
$setB = Set::of([Number::of(3), Number::of(4)]);

$union = $setA->union($setB);
$intersection = $setA->intersection($setB);
$difference = $setA->difference($setB);

$relation = Relation::of([
    [Number::of(1), Number::of(2)],
    [Number::of(2), Number::of(3)],
]);

$arithmetic = ArithmeticSequence::from(Number::of(3), Number::of(2));
$geometric = GeometricSequence::from(Number::of(2), Number::of(3));
$fibonacci = Recurrence::of(
    [Number::of(0), Number::of(1)],
    static fn (Number $a, Number $b): Number => $a->add($b),
);

$factorial = Factorial::of(Number::of(5));
$permutation = Permutation::of(Number::of(5), Number::of(2));
$combination = Combination::of(Number::of(5), Number::of(2));
$multinomial = Multinomial::of(Number::of(5), [
    Number::of(2),
    Number::of(2),
    Number::of(1),
]);

$gcd = GCD::of(Number::of(48), Number::of(18));
$lcm = LCM::of(Number::of(4), Number::of(6));
$totient = EulerTotient::of(Number::of(9));
$tau = DivisorFunctions::tau(Number::of(12));
$sigma = DivisorFunctions::sigma(Number::of(12));
$isPrime = Prime::isPrime(Number::of(13));
$nextPrime = Prime::nextPrime(Number::of(13));

$modPow = ModularArithmetic::power(Number::of(2), Number::of(10), Number::of(1000));
$modInverse = ModularInverse::of(Number::of(3), Number::of(11));

$factorization = Factorization::of(Number::of(360));
$primeFactors = $factorization->primeFactors();
$exponents = $factorization->exponents();
```

## Related modules

- [precision.md](precision.md)

- [algebra.md](algebra.md)
- [number.md](number.md)
- [probability.md](probability.md)
- [linear.md](linear.md)
- [optimization.md](optimization.md)