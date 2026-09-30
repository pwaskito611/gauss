# Number

## Purpose

`Number` is Gauss's exact decimal value type. It stores numeric values as normalized strings and keeps arithmetic precise enough for deterministic calculations without relying on PHP's native float behavior.

## Core type

- `Gauss\Number\Number`

The library uses `Number` for most arithmetic, including matrix entries, probability values, statistical calculations, and geometric distances.

## Creating numbers

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Number\Number;

$a = Number::of('10.5');
$b = Number::of(2);
$c = Number::of('0.25');

$pi = Number::pi();
$e = Number::e();

echo $a->value() . PHP_EOL;
echo $pi->value() . PHP_EOL;
```

`Number::of()` accepts integers, floats, strings, and existing `Number` instances. The float path is converted through the decimal helper, but string inputs are the safest way to preserve exact values.

## Arithmetic

```php
<?php

use Gauss\Number\Number;

$x = Number::of('10.5');
$y = Number::of('2.5');

$sum = $x->add($y);
$diff = $x->sub($y);
$product = $x->mul($y);
$ratio = $x->div($y);

echo $sum->value() . PHP_EOL;  // 13
echo $diff->value() . PHP_EOL; // 8
echo $product->value() . PHP_EOL; // 26.25
echo $ratio->value() . PHP_EOL; // 4.2
```

## Comparison and conversions

```php
<?php

use Gauss\Number\Number;

$left = Number::of('3.14');
$right = Number::of('3.15');

$comparison = $left->compare($right); // -1, 0, or 1
$absolute = Number::of('-12.5')->abs();

echo $comparison . PHP_EOL;
echo $absolute->value() . PHP_EOL; // 12.5
```

`compare()` returns the same ordering semantics as PHP's comparison, but with exact decimal values. `abs()` removes the sign and preserves the magnitude.

## Powers, roots, and exponentials

```php
<?php

use Gauss\Number\Number;

$x = Number::of('9');
$y = Number::of('2');

$squareRoot = $x->sqrt();
$square = $y->pow(2);
$exponential = Number::of('1')->exp();

echo $squareRoot->value() . PHP_EOL; // 3
echo $square->value() . PHP_EOL;      // 4
echo $exponential->value() . PHP_EOL; // 2.71828182845904523536028747135266249775724709369996
```

Use `pow()` for integer exponents, `sqrt()` for non-negative values, and `exp()` for the exponential function. `sqrt()` and `exp()` are exact within the library's decimal model and will reject invalid inputs such as negative square roots.

## Common methods

- `one()` returns the multiplicative identity.
- `add()`, `sub()`, `mul()`, `div()`, and `mod()` perform arithmetic.
- `compare()` compares two numbers exactly.
- `abs()`, `round()`, `pow()`, `sqrt()`, and `exp()` are common transformations.
- `value()`, `type()`, and `isIntegerLike()` expose the normalized numeric representation.

## Precision notes

`Number` is designed for exact decimal math when users supply decimal values as strings or integer literals. This is especially important for financial, statistical, and algebraic calculations that should not silently drift because of float rounding.

## Related modules

- [linear.md](linear.md)
- [algebra.md](algebra.md)
- [statistics.md](statistics.md)
- [probability.md](probability.md)
- [distribution.md](distribution.md)
