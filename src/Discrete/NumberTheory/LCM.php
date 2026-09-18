<?php

declare(strict_types=1);

namespace Gauss\Discrete\NumberTheory;

use Gauss\Number\Number;
use InvalidArgumentException;

final class LCM
{
    public static function of(Number $a, Number $b): Number
    {
        self::assertInteger($a);
        self::assertInteger($b);

        if ($a->compare(Number::of(0)) === 0 || $b->compare(Number::of(0)) === 0) {
            return Number::of(0);
        }

        $gcd = GCD::of($a, $b);
        $scaled = $a->abs()->div($gcd)->mul($b->abs());

        return $scaled;
    }

    private static function assertInteger(Number $value): void
    {
        if (! preg_match('/^-?\d+$/', $value->value())) {
            throw new InvalidArgumentException('LCM requires integer values.');
        }
    }
}
