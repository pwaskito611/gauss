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

        for ($value = Number::of(1); $value->compare($r) <= 0; $value = $value->add(1)) {
            $numerator = $n->sub($value->sub(1));
            $result = $result->mul($numerator)->div($value);
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
