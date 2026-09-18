<?php

declare(strict_types=1);

namespace Gauss\Discrete\NumberTheory;

use Gauss\Number\Number;
use InvalidArgumentException;

final class Coprime
{
    public static function of(Number $a, Number $b): bool
    {
        self::assertInteger($a);
        self::assertInteger($b);

        if ($a->compare(Number::of(0)) === 0 || $b->compare(Number::of(0)) === 0) {
            return false;
        }

        return GCD::of($a->abs(), $b->abs())->compare(Number::of(1)) === 0;
    }

    private static function assertInteger(Number $value): void
    {
        if (! preg_match('/^-?\d+$/', $value->value())) {
            throw new InvalidArgumentException('Coprime requires integer values.');
        }
    }
}
