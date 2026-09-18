<?php

declare(strict_types=1);

namespace Gauss\Discrete\NumberTheory;

use Gauss\Number\Number;
use InvalidArgumentException;

final class Prime
{
    public static function isPrime(Number $n): bool
    {
        if (! preg_match('/^-?\d+$/', $n->value())) {
            throw new InvalidArgumentException('Prime test requires an integer value.');
        }

        if ($n->compare(Number::of(2)) < 0) {
            return false;
        }

        if ($n->compare(Number::of(2)) === 0) {
            return true;
        }

        if ($n->compare(Number::of(2)) > 0 && $n->mod(2)->compare(Number::of(0)) === 0) {
            return false;
        }

        $limit = Number::of((int) floor(sqrt((float) $n->value())));
        for ($candidate = Number::of(3); $candidate->compare($limit) <= 0; $candidate = $candidate->add(2)) {
            if ($n->mod($candidate)->compare(Number::of(0)) === 0) {
                return false;
            }
        }

        return true;
    }
}
