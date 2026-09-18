<?php

declare(strict_types=1);

namespace Gauss\Discrete\NumberTheory;

use Gauss\Number\Number;
use InvalidArgumentException;

final class Congruence
{
    public static function of(Number $a, Number $b, Number $modulus): bool
    {
        self::assertInteger($a);
        self::assertInteger($b);
        self::assertInteger($modulus);

        if ($modulus->compare(Number::of(0)) <= 0) {
            throw new InvalidArgumentException('Congruence modulus must be positive.');
        }

        return Divisibility::isDivisibleBy($a->sub($b), $modulus);
    }

    private static function assertInteger(Number $value): void
    {
        if (! preg_match('/^-?\d+$/', $value->value())) {
            throw new InvalidArgumentException('Congruence requires integer values.');
        }
    }
}
