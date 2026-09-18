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
        $remainder = $a->sub($quotient->mul($b));

        if ($remainder->compare(Number::of(0)) < 0) {
            $adjustment = $b->compare(Number::of(0)) < 0 ? Number::of(1) : Number::of(-1);
            $quotient = $quotient->add($adjustment);
            $remainder = $a->sub($quotient->mul($b));
        }

        return $remainder;
    }

    public static function quotient(Number $a, Number $b): Number
    {
        self::assertInteger($a);
        self::assertInteger($b);

        if ($b->compare(Number::of(0)) === 0) {
            throw new DivisionByZeroError('Division by zero is undefined.');
        }

        $leftAbs = ltrim($a->abs()->value(), '-');
        $rightAbs = ltrim($b->abs()->value(), '-');
        $truncated = Number::of(bcdiv($leftAbs, $rightAbs, 0));

        $quotient = $a->compare(Number::of(0)) >= 0
            ? ($b->compare(Number::of(0)) >= 0 ? $truncated : $truncated->mul(Number::of(-1)))
            : ($b->compare(Number::of(0)) >= 0 ? $truncated->mul(Number::of(-1)) : $truncated);

        $remainder = $a->sub($quotient->mul($b));
        if ($remainder->compare(Number::of(0)) < 0) {
            $adjustment = $b->compare(Number::of(0)) < 0 ? Number::of(1) : Number::of(-1);
            $quotient = $quotient->add($adjustment);
        }

        return $quotient;
    }

    private static function assertInteger(Number $value): void
    {
        if (! preg_match('/^-?\d+$/', $value->value())) {
            throw new InvalidArgumentException('Divisibility requires integer values.');
        }
    }
}
