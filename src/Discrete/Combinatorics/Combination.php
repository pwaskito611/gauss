<?php

declare(strict_types=1);

namespace Gauss\Discrete\Combinatorics;

use Gauss\Number\Number;
use InvalidArgumentException;

final class Combination
{
    public static function of(Number $n, Number $r): Number
    {
        self::assertValidRange($n, $r);

        $r = $r->compare($n->sub($r)) <= 0 ? $r : $n->sub($r);
        $result = Number::of(1);

        for ($value = 1; $value <= (int) $r->value(); $value++) {
            $numerator = $n->sub(Number::of($value - 1));
            $denominator = Number::of($value);
            $result = $result->mul($numerator->div($denominator));
        }

        return $result;
    }

    private static function assertValidRange(Number $n, Number $r): void
    {
        if (! preg_match('/^-?\d+$/', $n->value()) || ! preg_match('/^-?\d+$/', $r->value())) {
            throw new InvalidArgumentException('Combination requires non-negative integer inputs.');
        }

        if ($n->compare(Number::of(0)) < 0 || $r->compare(Number::of(0)) < 0) {
            throw new InvalidArgumentException('Combination requires non-negative values.');
        }

        if ($r->compare($n) > 0) {
            throw new InvalidArgumentException('Combination requires 0 <= r <= n.');
        }
    }
}
