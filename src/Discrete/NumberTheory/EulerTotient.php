<?php

declare(strict_types=1);

namespace Gauss\Discrete\NumberTheory;

use Gauss\Number\Number;
use InvalidArgumentException;

final class EulerTotient
{
    public static function of(Number $n): Number
    {
        self::assertPositiveInteger($n);

        if ($n->compare(Number::of(1)) === 0) {
            return Number::of(1);
        }

        $result = Number::of($n->value());
        $factors = Factorization::of($n)->primeFactors();
        foreach ($factors as $factor) {
            $result = $result->sub($result->div($factor));
        }

        return $result;
    }

    private static function assertPositiveInteger(Number $value): void
    {
        if (! preg_match('/^-?\d+$/', $value->value())) {
            throw new InvalidArgumentException('EulerTotient requires a positive integer.');
        }

        if ($value->compare(Number::of(1)) < 0) {
            throw new InvalidArgumentException('EulerTotient requires n >= 1.');
        }
    }
}
