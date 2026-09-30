# Discrete

## Purpose

The discrete module focuses on finite, countable mathematical objects such as sets, sequences, and combinatorial functions. These APIs are intentionally small and composable, and they rely on `Number` for exact arithmetic with integer-like values.

## Core types

- `Gauss\Discrete\Set\Set`
- `Gauss\Discrete\Sequence\ArithmeticSequence`
- `Gauss\Discrete\Combinatorics\Combination`
- `Gauss\Discrete\Combinatorics\Factorial`
- `Gauss\Discrete\NumberTheory\GCD`

## Sets

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Discrete\Set\Set;
use Gauss\Number\Number;

$numbers = Set::of([
    Number::of(1),
    Number::of(2),
    Number::of(2),
    Number::of(3),
]);

$contains = $numbers->contains(Number::of(2));
$size = $numbers->count();

echo ($contains ? 'true' : 'false') . PHP_EOL;
echo $size . PHP_EOL; // 3
```

`Set::of()` normalizes duplicates and exposes set-like methods such as `contains()`, `union()`, `intersection()`, `difference()`, and `equals()`.

## Arithmetic sequences

```php
<?php

use Gauss\Discrete\Sequence\ArithmeticSequence;
use Gauss\Number\Number;

$sequence = ArithmeticSequence::from(Number::of(3), Number::of(2));

$first = $sequence->first();
$fifth = $sequence->at(Number::of(5));

echo $first->value() . PHP_EOL; // 3
echo $fifth->value() . PHP_EOL; // 11
```

Arithmetic sequences are defined by a first term and a common difference. The `at()` method returns the value at a positive integer index.

## Combinatorics

```php
<?php

use Gauss\Discrete\Combinatorics\Combination;
use Gauss\Number\Number;

$choose = Combination::of(Number::of(5), Number::of(2));

echo $choose->value() . PHP_EOL; // 10
```

The combinatorics helpers are integer-oriented and validate inputs such as $0 \le r \le n$. This keeps them aligned with the library's exact number semantics.

## Related modules

- [algebra.md](algebra.md)
- [number.md](number.md)
- [probability.md](probability.md)
