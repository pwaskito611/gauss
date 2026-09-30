# Algebra

## Purpose

The algebra module gives Gauss a symbolic polynomial layer built on top of exact `Number` arithmetic. The main public object is `Polynomial`, which stores sparse coefficients keyed by degree and supports standard polynomial arithmetic.

## Core types

- `Gauss\Algebra\Polynomial`
- `Gauss\Algebra\Monomial`
- `Gauss\Algebra\PolynomialDivision`

## Constructing a polynomial

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Algebra\Polynomial;
use Gauss\Number\Number;

$poly = Polynomial::of([
    0 => Number::of(2),
    1 => Number::of(3),
    2 => Number::of(1),
]);

// 1x^2 + 3x + 2
$degree = $poly->degree();
echo $degree . PHP_EOL; // 2
```

The array keys are exponents and the values are `Number` objects. This keeps the polynomial sparse and efficient when many coefficients are zero.

## Polynomial arithmetic

```php
<?php

use Gauss\Algebra\Polynomial;
use Gauss\Number\Number;

$a = Polynomial::of([
    0 => Number::of(1),
    1 => Number::of(2),
    2 => Number::of(1),
]);

$b = Polynomial::constant(Number::of(5));

$sum = $a->add($b);
$product = $a->mul($b);
$degree = $sum->degree();

echo $degree . PHP_EOL;
echo $product->coefficient(2)->value() . PHP_EOL;
```

Common operations are `add()`, `sub()`, `mul()`, `negate()`, `scale()`, and `divide()`. `coefficient($degree)` returns the value for a given exponent, while `constantTerm()` returns the constant part of the polynomial.

## Evaluating a polynomial

```php
<?php

use Gauss\Algebra\Polynomial;
use Gauss\Number\Number;

$poly = Polynomial::of([
    0 => Number::of(2),
    1 => Number::of(3),
    2 => Number::of(1),
]);

$value = $poly->evaluate(Number::of(4));

echo $value->value() . PHP_EOL; // 1*16 + 3*4 + 2 = 30
```

The `evaluate()` method calculates the polynomial at a specific numeric point. This is useful when a symbolic algebraic expression is combined with concrete numerical input.

## Related modules

- [number.md](number.md)
- [linear.md](linear.md)
- [numerical.md](numerical.md)
