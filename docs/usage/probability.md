# Probability

## Overview

The `Gauss\Probability` module provides a constrained `Probability` value,
finite sample spaces and events, probability measures, random variables, and
expectation, variance, and conditional-probability abstractions.

The main public types are:

- `Probability`
- `SampleSpace`
- `Event`
- `ProbabilityMeasure`
- `RandomVariable`
- `Expectation`
- `Variance`
- `ConditionalProbability`

`OutcomeIdentity` is an internal implementation helper used to consistently
identify supported PHP outcomes.

Numeric values are represented by `Gauss\Number\Number`. Addition,
subtraction, multiplication, and comparison use its decimal arithmetic.
Operations involving division follow `Number::div()`'s half-up rounding
behavior. Exact validation of a probability measure's total does not imply
that every probability operation is exact.

`ProbabilityMeasure` and `RandomVariable` factories, plus `Expectation::of()`
and `Variance::of()`, accept a trailing `bool $precision = true`. The default
selects BCMath; `false` selects float mode for the stored numeric weights and
values and for the expectation/variance calculation. See
[Precision modes](precision.md) for examples and limits.

## Probability

`Probability` represents a value in the closed interval `[0, 1]`.
`Probability::of()` validates and constructs the value. It accepts an integer,
float, numeric string, `Number`, or existing `Probability`.

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Probability\Probability;

$p = Probability::of('0.5');
$q = Probability::of('0.3');

$p->value()->value(); // "0.5"
$same = Probability::of($p);
var_dump($same === $p); // true
```

Values below zero or above one throw `InvalidArgumentException`. Numeric
conversion errors from `Number::of()` also propagate as `InvalidArgumentException`.

### Arithmetic

- `add(Probability $other): Probability` adds two probabilities. A result
  above one is rejected with `InvalidArgumentException`.
- `multiply(Probability $other): Probability` multiplies values and returns
  a `Probability`.
- `divide(Probability $other): Number` returns the general numeric ratio. It
  is deliberately a `Number`, not a `Probability`, because the ratio may
  exceed one. A zero divisor throws `DivisionByZeroError`.
- `complement(): Probability` returns `1 - P`.

```php
$p = Probability::of('0.5');
$q = Probability::of('0.3');

$p->add($q)->value()->value(); // "0.8"
$p->multiply($q)->value()->value(); // "0.15"
$p->divide(Probability::of('0.25'))->value(); // "2"
$p->complement()->value()->value(); // "0.5"
```

`add()`, `multiply()`, and `complement()` use `Number` addition,
multiplication, and subtraction respectively. `divide()` follows `Number`'s
rounded division behavior.

### Comparison and predicates

- `compare($other): int` compares against an integer, float, string, `Number`,
  or `Probability`, returning `-1`, `0`, or `1`.
- `isUnit(): bool` is true when the value equals one.
- `isZero(): bool` is true when the value equals zero.

```php
$p->compare('0.5'); // 0
Probability::of(1)->isUnit(); // true
Probability::of(0)->isZero(); // true
```

## Sample spaces

`SampleSpace` stores one or more distinct outcomes. An outcome may be `null`,
a boolean, integer, finite float, string, object, resource, or supported
array. Duplicate outcomes are removed using strict outcome identity, not
loose PHP equality. The first occurrence determines its position in the
stored outcomes.

```php
use Gauss\Probability\SampleSpace;

$space = SampleSpace::of('H', 'T', 'H');
$space->size(); // 2
```

An empty sample space throws `InvalidArgumentException`. Unsupported
outcomes, non-finite floats, and arrays containing references are also
rejected with `InvalidArgumentException`.

### Access and membership

- `outcomes(): array` and `values(): array` return the ordered list of unique
  outcomes.
- `contains($outcome): bool` checks strict outcome identity.
- `size(): int` returns the number of distinct outcomes.

```php
$space->outcomes();       // ["H", "T"]
$space->values();         // same outcome list
$space->contains('H');    // true
$space->contains('X');    // false
$space->size();           // 2
```

`contains(NAN)` returns `false`. `SampleSpace::of(NAN)` instead throws
`InvalidArgumentException`.

### Equality

`equals(SampleSpace $other): bool` compares the sets of strict outcome
identities, without regard to order:

```php
$space->equals(SampleSpace::of('T', 'H')); // true
```

## Events

An `Event` is a subset of a `SampleSpace`. Each event retains its sample
space, and duplicate event outcomes are normalized by strict outcome
identity.

### Construction and helpers

- `Event::of($space, ...$outcomes)` creates an event. Every outcome must
  belong to the sample space; otherwise `InvalidArgumentException` is thrown.
- `Event::all($space)` creates the event containing every outcome.
- `Event::empty($space)` creates the empty event.
- `sampleSpace(): SampleSpace`, `outcomes(): array`, and
  `contains($outcome): bool` inspect the event.
- `isEmpty(): bool` reports whether it has no outcomes.

```php
use Gauss\Probability\Event;

$heads = Event::of($space, 'H');
$all = Event::all($space);
$empty = Event::empty($space);

$heads->sampleSpace(); // $space
$heads->outcomes();    // ["H"]
$heads->contains('H'); // true
$empty->isEmpty();     // true
```

`Event::contains(NAN)` returns `false`; constructing an event with `NAN` is
invalid.

### Equality and set operations

`equals($other): bool` compares event membership and sample-space equality,
independent of event outcome order. Events over different sample spaces are
not equal; `equals()` returns `false`.

`union()`, `intersection()`, and `difference()` return events over the same
sample space. `complement()` returns all sample-space outcomes not in the
event. Binary operations require equal sample spaces; otherwise they throw
`InvalidArgumentException`.

```php
$tails = Event::of($space, 'T');

$heads->union($tails)->equals($all);        // true
$heads->intersection($tails)->isEmpty();    // true
$all->difference($heads)->equals($tails);   // true
$heads->complement()->equals($tails);       // true
```

## Probability measures

A `ProbabilityMeasure` assigns a `Probability` weight to each outcome in one
sample space.

### Positional construction with `of()`

`ProbabilityMeasure::of($space, $weights)` expects a positional list with
exactly one weight for each outcome, in sample-space order. Each weight may
be a `Probability`, integer, float, numeric string, or `Number`. Every weight
must be in `[0, 1]`, and their sum must compare exactly equal to `1`.
Incomplete lists, non-list arrays, invalid weights, or a total other than
exactly one throw `InvalidArgumentException`.

```php
use Gauss\Probability\ProbabilityMeasure;

$space = SampleSpace::of('A', 'B', 'C');
$measure = ProbabilityMeasure::of($space, ['0.1', '0.2', '0.7']);
```

The sum validation uses exact `Number` comparison, not approximate tolerance.

### Explicit maps with `fromMap()`

`ProbabilityMeasure::fromMap()` accepts either:

1. A PHP associative/keyed map, for outcomes usable as PHP array keys:

   ```php
   $measure = ProbabilityMeasure::fromMap(
       SampleSpace::of('H', 'T'),
       ['H' => '0.5', 'T' => '0.5']
   );
   ```

2. A list of `[outcome, weight]` pairs. This form supports outcomes that
   cannot be PHP array keys, such as objects and arrays, and distinguishes
   outcomes such as integer `1` and string `'1'`:

   ```php
   $first = new stdClass();
   $second = new stdClass();
   $space = SampleSpace::of($first, $second);

   $measure = ProbabilityMeasure::fromMap($space, [
       [$first, '0.25'],
       [$second, '0.75'],
   ]);
   ```

   The pair form is detected when the input is a non-empty PHP list and its
   first element is a two-element array. Otherwise the input is treated as a
   keyed map.

Both forms must map every sample-space outcome exactly once, with no unknown
or duplicate source outcomes, valid probability weights, and an exact total
of one. Violations throw `InvalidArgumentException`.

### Uniform measures

`uniform($space)` creates a measure intended to be uniform: for `N` outcomes
the target weight is `1 / N`. Since `1 / N` may be rounded by
`Number::div()`, the implementation assigns the first `N - 1` outcomes the
rounded unit weight and assigns the last outcome the remainder
`1 - sum(previous weights)`. The final stored weight can therefore differ
slightly from the others, while the stored weights still sum exactly to one.

```php
$coin = ProbabilityMeasure::uniform(SampleSpace::of('H', 'T'));
```

### Queries

- `sampleSpace(): SampleSpace` returns the associated sample space.
- `probabilityFor($outcome): Probability` returns one outcome's weight.
  An outcome outside the space throws `InvalidArgumentException`.
- `probabilityOf(Event $event): Probability` sums weights for the event.
  The event must use the same sample space or `InvalidArgumentException` is
  thrown. The empty event has probability zero.

```php
$measure->sampleSpace(); // $space
$measure->probabilityFor('A')->value()->value(); // "0.1"
$measure->probabilityOf(Event::of($space, 'A', 'C'))
    ->value()->value(); // "0.8"
```

`probabilityOf()` sums the stored `Number` values using addition and returns
the exact sum as a `Probability`.

### Conditional probability

`conditional(Event $a, Event $b): Probability` calculates
`P(A | B) = P(A ∩ B) / P(B)`. Both events must use the measure's sample space;
otherwise `InvalidArgumentException` is thrown. If `P(B) = 0`, it throws
`DivisionByZeroError`. The ratio uses `Number::div()` and is rounded according
to the `Number` precision model before being validated as a `Probability`.

```php
$conditional = $measure->conditional($eventA, $eventB);
$conditional->value()->value();
```

### Independence

`areIndependent(Event $a, Event $b): bool` checks whether
`P(A ∩ B) = P(A)P(B)`. Both events must use the measure's sample space or
`InvalidArgumentException` is thrown.

```php
$independent = $measure->areIndependent($eventA, $eventB);
```

Equality is evaluated by comparing the `Number` representations produced by
the calculation. In default BCMath mode, multiplication and comparison use
exact decimal arithmetic on those values; rounded division used to construct
or derive probabilities can affect later exact equality checks. Float mode
instead follows native float behavior.

## Random variables

A `RandomVariable` assigns a numeric `Number` value to every outcome in a
sample space.

### Positional mapping

`RandomVariable::of($space, $mapping)` requires a positional list of values
with exactly one value per sample-space outcome. Values may be integers,
floats, numeric strings, or `Number` objects and are converted through
`Number::of()`.

```php
$coin = SampleSpace::of('H', 'T');
$indicator = RandomVariable::of($coin, [1, 0]);
// H maps to 1; T maps to 0.
```

### Explicit mapping

`RandomVariable::fromMap()` supports a keyed map for outcomes usable as PHP
array keys or a list of `[outcome, value]` pairs. Pair-form detection follows
the same rule as `ProbabilityMeasure::fromMap()` and supports arbitrary
outcomes such as objects and arrays. The mapping must define exactly one
numeric value for every sample-space outcome; duplicate, missing, or unknown
outcomes throw `InvalidArgumentException`.

```php
$indicator = RandomVariable::fromMap($coin, [
    'H' => 1,
    'T' => 0,
]);
```

### Inspection and lookup

- `sampleSpace(): SampleSpace` returns the associated sample space.
- `mapping(): list<Number>` returns values in sample-space outcome order.
- `valueFor($outcome): Number` returns an outcome's numeric value, or throws
  `InvalidArgumentException` when the outcome is not in the sample space.

```php
$indicator->sampleSpace(); // $coin
$indicator->mapping();     // [Number(1), Number(0)]
$indicator->valueFor('H')->value(); // "1"
```

## Expectation

`Expectation::of($variable, $measure)` constructs an expectation. The random
variable and measure must use equal sample spaces; otherwise
`InvalidArgumentException` is thrown.

`value(): Number` returns the expected value
`E[X] = Σ P(ω)X(ω)`.

```php
$space = SampleSpace::of('H', 'T');
$measure = ProbabilityMeasure::uniform($space);
$payoff = RandomVariable::of($space, [1, 0]);

$expectedValue = Expectation::of($payoff, $measure)->value(); // Number("0.5")
```

The expectation itself uses multiplication and addition, with no additional
division. Its precision follows the stored probabilities and random-variable
values.

## Variance

`Variance::of($variable, $measure)` requires the variable and measure to use
equal sample spaces or throws `InvalidArgumentException`.
`value(): Number` computes the population variance:

```text
Var(X) = E[(X - E[X])²]
```

```php
$values = RandomVariable::of(SampleSpace::of(1, 2, 3, 4), [1, 2, 3, 4]);
$uniform = ProbabilityMeasure::uniform($values->sampleSpace());

$variance = Variance::of($values, $uniform)->value(); // Number("1.25")
```

The implementation uses expectation, subtraction, integer `pow(2)`,
multiplication, and addition. It adds no division beyond the arithmetic
already used to construct the underlying probabilities; integer powers and
the variance accumulation use `Number` operations.

## ConditionalProbability

`ConditionalProbability` is a wrapper around
`ProbabilityMeasure::conditional()`.
`ConditionalProbability::of($eventA, $eventB, $measure)` returns an object
whose `value(): Probability` is `P(A | B)`.

```php
$conditional = ConditionalProbability::of($eventA, $eventB, $measure);
$probability = $conditional->value();
```

Different sample spaces cause `InvalidArgumentException`. If `P(B) = 0`,
`DivisionByZeroError` is thrown by the underlying conditional calculation.

## Outcome identity

`OutcomeIdentity` is an internal identity mechanism, not a statistical
concept or a primary user-facing API. It allows sample spaces, events,
measures, and random variables to consistently refer to outcomes of different
PHP types.

Supported outcomes are `null`, booleans, integers, finite floats, strings,
objects, resources, and arrays whose nested values are also supported and
contain no references. `NAN`, infinities, arrays containing references, and
unsupported values throw `InvalidArgumentException` when used to construct
sample spaces or mappings.

Identity rules include:

- Values are type-sensitive: integer `1`, float `1.0`, string `'1'`, and
  boolean `true` are distinct.
- Positive and negative floating zero (`0.0` and `-0.0`) share an identity;
  other finite floats use their IEEE-754 representation.
- Objects are identified by `spl_object_id()`, not by property values.
- Resources are identified by resource type and `get_resource_id()`.
- List array order is significant. Associative-array keys are normalized
  into a stable order before identity is formed.

## Precision model

The table describes the default BCMath mode. Probability values, measures,
random variables, and expectation/variance computations can also use float
mode via `offPrecision()` or the factory precision selector described above.

| Operation | Arithmetic | Precision behavior |
| --- | --- | --- |
| `Probability::of()` | Range checks and comparison | Exact validation against `[0, 1]` |
| `Probability::add()` | `Number::add()` | Exact on stored decimal values; rejects a result above one |
| `Probability::multiply()` | `Number::mul()` | Exact on stored decimal values |
| `Probability::complement()` | `Number::sub()` | Exact on stored decimal values |
| `Probability::divide()` | `Number::div()` | Half-up rounded |
| `ProbabilityMeasure::probabilityOf()` | Addition | Exact sum of stored probabilities |
| `ProbabilityMeasure::conditional()` | Division | Half-up rounded |
| `ProbabilityMeasure::uniform()` | Division and remainder adjustment | Rounded unit weights; total is adjusted to exactly one |
| `ProbabilityMeasure::areIndependent()` | Addition, multiplication, comparison | Exact equality over the represented values; upstream rounded calculations may affect results |
| `Expectation::value()` | Multiplication and addition | Exact over the represented probability/value inputs |
| `Variance::value()` | Expectation, subtraction, integer power, multiplication, addition | Exact accumulation over the represented inputs |

“Exact” describes decimal arithmetic on the values stored in `Number`; it
does not recover precision lost before conversion from a PHP float.
Operations involving `div()` follow Gauss's `Number` half-up rounding model.
In particular, exact validation of a measure's probability sum does not mean
that every probability operation is exact.

## Exceptions and edge cases

| API | Exception | Condition |
| --- | --- | --- |
| `Probability::of()` | `InvalidArgumentException` | Value is outside `[0, 1]` or cannot be converted by `Number::of()` |
| `Probability::add()` | `InvalidArgumentException` | Sum is greater than `1` |
| `Probability::divide()` | `DivisionByZeroError` | Divisor probability is zero |
| `SampleSpace::of()` | `InvalidArgumentException` | No outcomes or an unsupported/invalid outcome |
| `Event::of()` | `InvalidArgumentException` | Outcome is not in its sample space |
| Event `union()`, `intersection()`, `difference()` | `InvalidArgumentException` | Events use different sample spaces |
| `ProbabilityMeasure::of()` | `InvalidArgumentException` | Weights are not a positional list of the correct size, contain invalid probabilities, or do not sum exactly to `1` |
| `ProbabilityMeasure::fromMap()` | `InvalidArgumentException` | Invalid pair/map form, missing, extra, or duplicate outcome, invalid probability, or total not exactly `1` |
| `ProbabilityMeasure::probabilityOf()` | `InvalidArgumentException` | Event uses a different sample space |
| `ProbabilityMeasure::probabilityFor()` | `InvalidArgumentException` | Outcome is not in the sample space |
| `ProbabilityMeasure::conditional()` | `InvalidArgumentException` | Either event uses a different sample space |
| `ProbabilityMeasure::conditional()` | `DivisionByZeroError` | Conditioning event has probability zero |
| `ProbabilityMeasure::areIndependent()` | `InvalidArgumentException` | Either event uses a different sample space |
| `RandomVariable::of()` | `InvalidArgumentException` | Mapping is not a positional list with one value per outcome |
| `RandomVariable::fromMap()` | `InvalidArgumentException` | Invalid pair/map form, missing, extra, or duplicate outcome, or value cannot be converted to `Number` |
| `RandomVariable::valueFor()` | `InvalidArgumentException` | Outcome is not in the sample space |
| `Expectation::of()` | `InvalidArgumentException` | Variable and measure use different sample spaces |
| `Variance::of()` | `InvalidArgumentException` | Variable and measure use different sample spaces |
| `ConditionalProbability::of()` | `InvalidArgumentException` | Events do not use the measure's sample space |
| `ConditionalProbability::of()` | `DivisionByZeroError` | Conditioning event has probability zero |
| Outcome identity construction | `InvalidArgumentException` | Unsupported outcome, non-finite float, or array containing references |

`SampleSpace::contains(NAN)` and `Event::contains(NAN)` are special cases:
they return `false` instead of throwing. A `Probability` arithmetic result
must remain in `[0, 1]`; the general numeric ratio from `divide()` is the
exception and is returned as `Number`.

## Complete example

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Probability\ConditionalProbability;
use Gauss\Probability\Event;
use Gauss\Probability\Expectation;
use Gauss\Probability\ProbabilityMeasure;
use Gauss\Probability\RandomVariable;
use Gauss\Probability\SampleSpace;
use Gauss\Probability\Variance;

$space = SampleSpace::of(1, 2, 3, 4, 5, 6);
$even = Event::of($space, 2, 4, 6);
$greaterThanThree = Event::of($space, 4, 5, 6);
$measure = ProbabilityMeasure::uniform($space);

$dieValue = RandomVariable::fromMap($space, [
    1 => 1,
    2 => 2,
    3 => 3,
    4 => 4,
    5 => 5,
    6 => 6,
]);

$evenProbability = $measure->probabilityOf($even);
$conditional = ConditionalProbability::of($even, $greaterThanThree, $measure);
$expectedValue = Expectation::of($dieValue, $measure)->value();
$variance = Variance::of($dieValue, $measure)->value();
$independent = $measure->areIndependent($even, $greaterThanThree);
```

## Related modules

- [distribution.md](distribution.md)
- [statistics.md](statistics.md)
- [number.md](number.md)
