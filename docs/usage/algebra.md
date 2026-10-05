# Algebra

## Overview

The algebra module provides a small symbolic layer for polynomial arithmetic built on top of exact `Number` values. The main public types are:

- `Gauss\Algebra\Monomial`
- `Gauss\Algebra\Polynomial`
- `Gauss\Algebra\PolynomialDivision`

All algebraic objects are immutable; operations do not mutate the original instances.

The library models polynomials sparsely: coefficients are stored by degree, so zero terms are omitted automatically. This makes operations such as `x^2 + 1` lightweight to represent and reason about.

## Number integration

Polynomial coefficients are not primitive PHP numbers. They are always `Gauss\Number\Number` objects.

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Algebra\Polynomial;
use Gauss\Number\Number;

$three = Number::of(3);

$poly = Polynomial::of([
    0 => Number::of(2),
    1 => Number::of(3),
    2 => Number::of(1),
]);

$scaled = $poly->scale($three);
echo $scaled->coefficient(1)->value(); // 9
```

This matters because polynomial arithmetic delegates numeric behavior and precision to `Number` rather than PHP's floating-point arithmetic.

## Monomial

A `Monomial` represents a single term of the form `c * x^n`.

```php
<?php

use Gauss\Algebra\Monomial;
use Gauss\Number\Number;

$m = new Monomial(Number::of(3), 2);
```

Conceptually, this is `3x^2`.

### Construction

```php
new Monomial(Number $coefficient, int $degree = 0)
```

Examples:

```php
$m = new Monomial(Number::of(3), 2);      // 3x^2
$c = new Monomial(Number::of(7));         // 7
$zero = new Monomial(Number::of(0), 5);   // canonical zero monomial
```

A negative degree is rejected:

```php
new Monomial(Number::of(3), -1); // InvalidArgumentException
```

### Inspection

```php
$m = new Monomial(Number::of(3), 2);

$coeff = $m->coefficient(); // Number(3)
$deg   = $m->degree();      // 2
```

### Evaluation

```php
$m = new Monomial(Number::of(3), 2);

$value = $m->evaluate(Number::of(2));

echo $value->value(); // 12
```

This computes `3 * 2^2 = 12` through `Number` arithmetic.

### Arithmetic

Monomials support addition and multiplication when the operation is valid.

```php
$m = new Monomial(Number::of(3), 2);
$n = new Monomial(Number::of(4), 2);

$sum = $m->add($n);      // 7x^2
$product = $m->mul(new Monomial(Number::of(4), 3)); // 12x^5
```

When both monomials are non-zero, addition requires the same degree:

```php
(new Monomial(Number::of(3), 2))
    ->add(new Monomial(Number::of(4), 3));
// InvalidArgumentException
```

Multiplication adds exponents:

```php
(3x^2) * (4x^3) = 12x^5
```

### Calculus

```php
$m = new Monomial(Number::of(3), 2);

$derivative = $m->derivative(); // 6x
$integral   = $m->integral();   // x^3
```

The methods follow the standard symbolic rules:

- derivative: `d/dx (c * x^n) = c * n * x^(n-1)`
- integral: `∫ c * x^n dx = c / (n + 1) * x^(n + 1)`

These operations delegate arithmetic, including its precision behavior, to `Number`.

### State predicates

```php
$m = new Monomial(Number::of(3), 2);

$m->isConstant(); // false
$m->isZero();     // false
```

The predicates mean:

- `isConstant()` → degree is 0
- `isZero()` → coefficient is zero

A zero coefficient monomial is normalized to degree 0, so zero monomials are canonicalized.

### Zero monomial semantics

```php
$zero = new Monomial(Number::of(0), 5);

echo $zero->degree(); // 0
var_dump($zero->isZero()); // true
```

The zero monomial collapses to a canonical degree-0 zero term.

## Polynomial

A `Polynomial` is the core object for symbolic algebra in Gauss. It stores sparse coefficients keyed by exponent.

### Construction

```php
<?php

use Gauss\Algebra\Polynomial;
use Gauss\Number\Number;

$poly = Polynomial::of([
    0 => Number::of(2),
    1 => Number::of(3),
    2 => Number::of(1),
]);
```

This represents:

```text
x^2 + 3x + 2
```

The array keys are exponents and the values are `Number` objects. Zero coefficients are dropped automatically during normalization.

You can also create zero and constant polynomials:

```php
$zero = Polynomial::zero(Number::of(0));
$const = Polynomial::constant(Number::of(5));
$one = Polynomial::one(Number::of(0)); // 1
```

`Polynomial::one(Number $reference)` creates the constant polynomial `1`.
`Polynomial::constant(Number::of(0))` creates the zero polynomial.

`Polynomial::of([])` is rejected because a polynomial must contain at least one coefficient.

### Inspection

```php
$poly = Polynomial::of([
    0 => Number::of(2),
    1 => Number::of(3),
    2 => Number::of(1),
]);

$poly->degree();             // 2
$poly->coefficient(1);       // Number(3)
$poly->coefficient(3);       // Number(0)
$poly->constantTerm();       // Number(2)
$poly->leadingCoefficient(); // Number(1)
```

The polynomial keeps sparse terms internally, but `coefficient($degree)` always returns a `Number`, including zero for missing exponents.

`coefficients()` returns the polynomial's sparse coefficient representation as
`array<int, Number>`, keyed by degree:

```php
$coefficients = $poly->coefficients();
// => [0 => Number(2), 1 => Number(3), 2 => Number(1)]
```

The keys are degrees and the values are their corresponding coefficients;
zero-valued coefficients are omitted.

```php
$terms = $poly->terms();
// => [Monomial(2, 0), Monomial(3, 1), Monomial(1, 2)]
```

Terms are returned in ascending degree order, from the lowest degree to the
highest degree.

`isConstant()` returns `true` when the polynomial has degree `0`:

```php
$p = Polynomial::of([
    0 => Number::of(5),
]);

$p->isConstant(); // true
```

The zero polynomial is also considered constant because its degree is `0`.

`isMonic()` returns `true` when a non-zero polynomial's leading coefficient
is `1`:

```php
$p = Polynomial::of([
    0 => Number::of(1),
    1 => Number::of(1),
]);
$q = Polynomial::of([
    0 => Number::of(1),
    1 => Number::of(2),
]);

$p->isMonic(); // true
$q->isMonic(); // false
```

The zero polynomial is not monic.

Requesting a coefficient at a negative degree throws
`InvalidArgumentException`:

```php
$poly->coefficient(-1); // InvalidArgumentException
```

### Evaluation

```php
$poly = Polynomial::of([
    0 => Number::of(2),
    1 => Number::of(3),
    2 => Number::of(1),
]);

$value = $poly->evaluate(Number::of(4));

echo $value->value(); // 30
```

The implementation uses Horner-style evaluation under the hood and returns a `Number` result.

### Arithmetic

```php
$a = Polynomial::of([
    0 => Number::of(1),
    1 => Number::of(2),
    2 => Number::of(1),
]);

$b = Polynomial::of([
    0 => Number::of(3),
    1 => Number::of(-2),
    2 => Number::of(1),
]);

$sum = $a->add($b);
$diff = $a->sub($b);
$product = $a->mul($b);
```

Common methods:

- `add(Polynomial $other)`
- `sub(Polynomial $other)`
- `mul(Polynomial $other)`
- `negate()`
- `scale(Number $scalar)`

Examples:

```php
$negated = $a->negate();
$scaled  = $a->scale(Number::of(3));
```

### Scaling and negation

```php
$poly = Polynomial::of([
    0 => Number::of(2),
    1 => Number::of(3),
]);

$scaled = $poly->scale(Number::of(5)); // 15x + 10
$negated = $poly->negate();             // -3x - 2
```

`scale()` multiplies every coefficient by the supplied `Number` value. `negate()` is equivalent to multiplying the polynomial by `-1`.

### Polynomial division

Division is performed with `divide()` and returns a `PolynomialDivision` result containing both quotient and remainder.

```php
<?php

use Gauss\Algebra\Polynomial;
use Gauss\Number\Number;

$dividend = Polynomial::of([
    0 => Number::of(1),
    2 => Number::of(1),
]);

$divisor = Polynomial::of([
    0 => Number::of(1),
    1 => Number::of(1),
]);

$division = $dividend->divide($divisor);

$quotient = $division->quotient();
$remainder = $division->remainder();
```

This represents:

```text
x^2 + 1 = (x - 1)(x + 1) + 2
```

The identity preserved by the library is:

```text
dividend = quotient * divisor + remainder
```

Division by zero is invalid:

```php
Polynomial::of([0 => Number::of(1)])
    ->divide(Polynomial::zero(Number::of(0)));
// DivisionByZeroError
```

### Calculus

Polynomials support symbolic differentiation and integration.

```php
$poly = Polynomial::of([
    0 => Number::of(1),
    1 => Number::of(2),
    2 => Number::of(3),
]);

$derivative = $poly->derivative();
$integral = $poly->integral(Number::of(5));
```

For `3x^2 + 2x + 1`:

```text
derivative = 6x + 2
integral = x^3 + x^2 + x + 5
```

The integration method accepts a constant of integration as a `Number`, because the library models the antiderivative as a polynomial plus an explicit constant term.

### Zero polynomial

Zero polynomials are canonicalized and stored sparsely.

```php
$zero = Polynomial::zero(Number::of(0));

$zero->isZero(); // true
$zero->degree(); // 0
```

A zero-valued constant also produces the zero polynomial:

```php
$zero = Polynomial::constant(Number::of(0));

$zero->degree();             // 0
$zero->isZero();             // true
$zero->isConstant();         // true
$zero->leadingCoefficient(); // Number(0)
$zero->isMonic();            // false
```

A polynomial whose coefficients are all zero is treated as the zero
polynomial. Its degree is represented as `0`.

### Exceptions

The algebra API may throw exceptions when invalid input is provided:

- `InvalidArgumentException`
  - negative degree in `Monomial`
  - negative degree in polynomial coefficients
  - empty polynomial array in `Polynomial::of([])`
- `DivisionByZeroError`
  - dividing by the zero polynomial
- `RuntimeException`
  - non-progressing polynomial division path

Adding two non-zero monomials with different degrees throws
`InvalidArgumentException`:

```php
$m = new Monomial(Number::of(2), 2);
$n = new Monomial(Number::of(3), 1);

$m->add($n); // InvalidArgumentException
```

Requesting a polynomial coefficient at a negative degree also throws
`InvalidArgumentException`:

```php
$poly->coefficient(-1); // InvalidArgumentException
```

## PolynomialDivision

`PolynomialDivision` is the immutable result of a successful division.

```php
$division = $dividend->divide($divisor);

$quotient = $division->quotient();
$remainder = $division->remainder();
```

### Construction / obtaining a division

Usually you obtain it from `Polynomial::divide()`:

```php
$division = $p->divide($q);
```

The class itself accepts `Polynomial` objects for quotient and remainder and stores them as given:

```php
use Gauss\Algebra\PolynomialDivision;

new PolynomialDivision($quotient, $remainder);
```

The constructor does not perform polynomial division or validate the division identity.

### Quotient

```php
$division->quotient();
```

Returns the polynomial quotient `Q(x)`.

### Remainder

```php
$division->remainder();
```

Returns the polynomial remainder `R(x)` such that:

```text
P(x) = Q(x) * D(x) + R(x)
```

If the remainder is non-zero, its degree is strictly less than the divisor
degree. A zero remainder is represented by the zero polynomial, whose degree
is `0`.

### Division identity

This is the central contract of the result:

```php
$reconstructed = $division->quotient()->mul($divisor)->add($division->remainder());
```

For a result returned by `Polynomial::divide()`, the reconstructed polynomial
is equivalent to the original dividend.

## Working with polynomial terms

The `terms()` method exposes a polynomial as an array of `Monomial` objects.

```php
$poly = Polynomial::of([
    0 => Number::of(1),
    1 => Number::of(2),
    2 => Number::of(3),
]);

foreach ($poly->terms() as $term) {
    echo $term->coefficient()->value() . 'x^' . $term->degree() . PHP_EOL;
}
```

This is useful when you want to inspect or transform specific terms without reconstructing the full polynomial structure by hand.

## Complete example

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Algebra\Monomial;
use Gauss\Algebra\Polynomial;
use Gauss\Number\Number;

$term = new Monomial(Number::of(3), 2);
$evaluated = $term->evaluate(Number::of(4));

echo $evaluated->value(); // 48

$poly = Polynomial::of([
    0 => Number::of(1),
    1 => Number::of(2),
    2 => Number::of(3),
]);

$derivative = $poly->derivative();
$integral = $poly->integral(Number::of(5));

$division = $poly->divide(Polynomial::of([
    0 => Number::of(1),
    1 => Number::of(1),
]));

$quotient = $division->quotient();
$remainder = $division->remainder();

echo $quotient->degree() . PHP_EOL;
echo $remainder->constantTerm()->value() . PHP_EOL;
```

This example combines monomial arithmetic, polynomial inspection, differentiation, integration, and division into a single working flow.

## Edge cases and exceptions

A few edge cases are worth keeping in mind when using the algebra API:

- Zero coefficients are normalized away from sparse storage.
- Zero monomials and zero polynomials are canonicalized to degree 0.
- `Monomial::add()` refuses different degrees when both monomials are non-zero.
- `Polynomial::of([])` is rejected.
- `Polynomial::divide()` rejects a zero divisor with `DivisionByZeroError`.
- `evaluate()` and arithmetic operate through `Number`, so numeric precision follows `Number` rather than PHP floats.

## Related modules

- [number.md](number.md)
- [linear.md](linear.md)
- [numerical.md](numerical.md)
