<?php

declare(strict_types=1);

namespace Gauss\Discrete\Combinatorics;

use Gauss\Number\Number;
use InvalidArgumentException;

final class Multinomial
{
    /**
     * @param list<Number> $parts
     */
    public static function of(Number $n, array $parts): Number
    {
        self::assertValid($n, $parts);

        $result = Factorial::of($n);
        foreach ($parts as $part) {
            $result = $result->div(Factorial::of($part));
        }

        return $result;
    }

    /**
     * @param list<Number> $parts
     */
    private static function assertValid(Number $n, array $parts): void
    {
        if (! preg_match('/^-?\d+$/', $n->value())) {
            throw new InvalidArgumentException('Multinomial requires integer n.');
        }

        if ($n->compare(Number::of(0)) < 0) {
            throw new InvalidArgumentException('Multinomial requires non-negative n.');
        }

        if ($parts === []) {
            throw new InvalidArgumentException('At least one part is required.');
        }

        $sum = Number::of(0);
        foreach ($parts as $part) {
            if (! preg_match('/^-?\d+$/', $part->value())) {
                throw new InvalidArgumentException('Multinomial requires integer parts.');
            }
            if ($part->compare(Number::of(0)) < 0) {
                throw new InvalidArgumentException('Multinomial parts must be non-negative.');
            }
            $sum = $sum->add($part);
        }

        if ($sum->compare($n) !== 0) {
            throw new InvalidArgumentException('Multinomial parts must sum to n.');
        }
    }
}
