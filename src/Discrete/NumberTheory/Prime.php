<?php

declare(strict_types=1);

namespace Gauss\Discrete\NumberTheory;

use Gauss\Number\Number;
use InvalidArgumentException;

final class Prime
{
    public static function isPrime(Number $n): bool
    {
        self::assertInteger($n);

        if ($n->compare(Number::of(2)) < 0) {
            return false;
        }

        if ($n->compare(Number::of(2)) === 0) {
            return true;
        }

        if ($n->compare(Number::of(2)) > 0 && $n->mod(2)->compare(Number::of(0)) === 0) {
            return false;
        }

        $limit = IntegerSquareRoot::of($n);
        for ($candidate = Number::of(3); $candidate->compare($limit) <= 0; $candidate = $candidate->add(2)) {
            if ($n->mod($candidate)->compare(Number::of(0)) === 0) {
                return false;
            }
        }

        return true;
    }

    public static function nextPrime(Number $n): Number
    {
        self::assertInteger($n);

        if ($n->compare(Number::of(2)) < 0) {
            return Number::of(2);
        }

        $candidate = $n->compare(Number::of(2)) === 0
            ? Number::of(3)
            : $n->add($n->mod(2)->compare(Number::of(0)) === 0 ? 1 : 2);

        while (! self::isPrime($candidate)) {
            $candidate = $candidate->add(2);
        }

        return $candidate;
    }

    private static function assertInteger(Number $value): void
    {
        if (! preg_match('/^-?\d+$/', $value->value())) {
            throw new InvalidArgumentException('Prime test requires an integer value.');
        }
    }
}
