<?php

declare(strict_types=1);

namespace Gauss\Discrete\Combinatorics;

use Gauss\Number\Number;
use InvalidArgumentException;

final class Permutation
{
    public static function of(Number $n, Number $r): Number
    {
        self::assertValidRange($n, $r);

        $result = Number::of(1);
        $upper = (int) $n->value();
        $count = (int) $r->value();

        for ($value = $upper - $count + 1; $value <= $upper; $value++) {
            $result = $result->mul(Number::of($value));
        }

        return $result;
    }

    private static function assertValidRange(Number $n, Number $r): void
    {
        if (! preg_match('/^-?\d+$/', $n->value()) || ! preg_match('/^-?\d+$/', $r->value())) {
            throw new InvalidArgumentException('Permutation requires non-negative integer inputs.');
        }

        if ($n->compare(Number::of(0)) < 0 || $r->compare(Number::of(0)) < 0) {
            throw new InvalidArgumentException('Permutation requires non-negative values.');
        }

        if ($r->compare($n) > 0) {
            throw new InvalidArgumentException('Permutation requires 0 <= r <= n.');
        }
    }
}
