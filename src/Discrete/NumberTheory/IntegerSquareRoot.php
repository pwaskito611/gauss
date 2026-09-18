<?php

declare(strict_types=1);

namespace Gauss\Discrete\NumberTheory;

use Gauss\Number\Number;
use InvalidArgumentException;

final class IntegerSquareRoot
{
    public static function of(Number $n): Number
    {
        self::assertInteger($n);

        if ($n->compare(Number::of(0)) < 0) {
            throw new InvalidArgumentException('Integer square root requires a non-negative integer.');
        }

        if ($n->compare(Number::of(0)) === 0 || $n->compare(Number::of(1)) === 0) {
            return $n;
        }

        $low = Number::of(0);
        $high = $n;

        while ($low->compare($high) < 0) {
            $mid = Number::of(
                (string) (int) bcdiv(
                    $low->add($high)->value(),
                    '2',
                    0
                )
            );
            $square = $mid->mul($mid);

            if ($square->compare($n) <= 0) {
                $low = $mid->add(1);
            } else {
                $high = $mid;
            }
        }

        return $low->sub(1);
    }

    private static function assertInteger(Number $value): void
    {
        if (! preg_match('/^-?\d+$/', $value->value())) {
            throw new InvalidArgumentException('IntegerSquareRoot requires integer values.');
        }
    }
}
