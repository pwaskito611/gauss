<?php

declare(strict_types=1);

namespace Gauss\Numerical\Integration;

use Gauss\Number\Number;
use InvalidArgumentException;

final class TrapezoidalRule
{
    private function __construct()
    {
    }

    /**
     * @param callable(Number): Number $function
     */
    public static function integrate(
        callable $function,
        int|float|string|Number $lower,
        int|float|string|Number $upper,
        int $subdivisions = 100,
    ): Number {
        $a = Number::of($lower);
        $b = Number::of($upper);

        if ($subdivisions <= 0) {
            throw new InvalidArgumentException('The number of subdivisions must be positive.');
        }

        if ($a->compare($b) > 0) {
            throw new InvalidArgumentException('The integration interval must satisfy lower <= upper.');
        }

        if ($a->compare($b) === 0) {
            return Number::of(0);
        }

        $n = Number::of($subdivisions);
        $step = $b->sub($a)->div($n);
        $sum = $function($a)->add($function($b));

        for ($index = 1; $index < $subdivisions; $index++) {
            $xi = $a->add(Number::of($index)->mul($step));
            $sum = $sum->add(
                Number::of(2)->mul($function($xi))
            );
        }

        return $step->mul($sum)->div(2);
    }
}
