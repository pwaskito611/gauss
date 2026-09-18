<?php

declare(strict_types=1);

namespace Gauss\Discrete\NumberTheory;

use Gauss\Number\Number;
use InvalidArgumentException;

final class GCD
{
    public static function of(Number $a, Number $b): Number
    {
        self::assertInteger($a);
        self::assertInteger($b);

        $left = $a->abs();
        $right = $b->abs();

        if ($left->compare(Number::of(0)) === 0 && $right->compare(Number::of(0)) === 0) {
            return Number::of(0);
        }

        while ($right->compare(Number::of(0)) !== 0) {
            $remainder = Divisibility::remainder($left, $right);
            $left = $right;
            $right = $remainder->abs();
        }

        return $left;
    }

    private static function assertInteger(Number $value): void
    {
        if (! preg_match('/^-?\d+$/', $value->value())) {
            throw new InvalidArgumentException('GCD requires integer values.');
        }
    }
}
