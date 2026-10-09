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
        bool $precision = true,
    ): Number {
        $a = Number::of($lower)->withBackend(! $precision);
        $b = Number::of($upper)->withBackend(! $precision);

        if ($subdivisions <= 0) {
            throw new InvalidArgumentException('The number of subdivisions must be positive.');
        }

        if ($a->compare($b) > 0) {
            throw new InvalidArgumentException('The integration interval must satisfy lower <= upper.');
        }

        if ($a->compare($b) === 0) {
            return Number::of(0)->withBackend($a->usesFloatBackend() || $b->usesFloatBackend());
        }

        $n = Number::of($subdivisions);
        $step = $b->sub($a)->div($n);
        $evaluate = static function (Number $point) use ($function): Number {
            $value = Number::of($function($point));
            return $value->withBackend($point->usesFloatBackend());
        };
        $sum = $evaluate($a)->add($evaluate($b));
        $xi = $a;

        for ($index = 1; $index < $subdivisions; $index++) {
            $xi = $xi->add($step);
            $sum = $sum->add(
                Number::of(2)->mul($evaluate($xi))
            );
        }

        return $step->mul($sum)->div(2);
    }
}
