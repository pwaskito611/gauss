<?php

declare(strict_types=1);

namespace Gauss\Discrete\NumberTheory;

use DivisionByZeroError;
use Gauss\Number\Number;
use InvalidArgumentException;

final class Divisibility
{
    public static function isDivisibleBy(Number $a, Number $b): bool
    {
        self::assertInteger($a);
        self::assertInteger($b);

        if ($b->compare(Number::of(0)) === 0) {
            throw new DivisionByZeroError('Division by zero is undefined.');
        }

        return self::remainder($a, $b)->compare(Number::of(0)) === 0;
    }

    public static function remainder(Number $a, Number $b): Number
    {
        self::assertInteger($a);
        self::assertInteger($b);

        if ($b->compare(Number::of(0)) === 0) {
            throw new DivisionByZeroError('Division by zero is undefined.');
        }

        $quotient = self::quotient($a, $b);
        return $a->sub($quotient->mul($b));
    }

    private static function quotient(Number $a, Number $b): Number
    {
        $leftValue = $a->value();
        $rightValue = $b->value();

        $leftAbs = ltrim($leftValue, '-');
        $rightAbs = ltrim($rightValue, '-');

        $quotient = bcdiv($leftAbs, $rightAbs, 0);
        $negative = (($leftValue[0] ?? '+') === '-' xor ($rightValue[0] ?? '+') === '-');

        if ($negative) {
            $quotient = '-' . ltrim($quotient, '-');
        }

        return Number::of($quotient);
    }

    private static function assertInteger(Number $value): void
    {
        if (! preg_match('/^-?\d+$/', $value->value())) {
            throw new InvalidArgumentException('Divisibility requires integer values.');
        }
    }
}
