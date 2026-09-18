<?php

declare(strict_types=1);

namespace Gauss\Discrete\Combinatorics;

use Gauss\Number\Number;
use InvalidArgumentException;

final class Factorial
{
    public static function of(Number $n): Number
    {
        self::assertNonNegativeInteger($n);

        if ($n->compare(Number::of(0)) === 0 || $n->compare(Number::of(1)) === 0) {
            return Number::of(1);
        }

        $result = Number::of(1);
        for ($value = 2; $value <= (int) $n->value(); $value++) {
            $result = $result->mul(Number::of($value));
        }

        return $result;
    }

    private static function assertNonNegativeInteger(Number $n): void
    {
        if (! preg_match('/^-?\d+$/', $n->value())) {
            throw new InvalidArgumentException('Factorial expects a non-negative integer value.');
        }

        if ($n->compare(Number::of(0)) < 0) {
            throw new InvalidArgumentException('Factorial is undefined for negative values.');
        }
    }
}
