<?php

declare(strict_types=1);

namespace Gauss\Numerical\Differentiation;

use Gauss\Number\Number;
use InvalidArgumentException;

final class BackwardDifference
{
    private function __construct()
    {
    }

    /**
     * @param callable(Number): Number $function
     */
    public static function approximate(
        callable $function,
        int|float|string|Number $x,
        int|float|string|Number $step,
        bool $precision = true,
    ): Number {
        $value = Number::of($x)->withBackend(! $precision);
        $h = Number::of($step)->withBackend(! $precision);

        if ($h->compare(0) === 0) {
            throw new InvalidArgumentException('Step size must not be zero.');
        }

        $evaluate = static function (Number $point) use ($function): Number {
            $result = Number::of($function($point));
            return $result->withBackend($point->usesFloatBackend());
        };

        return $evaluate($value)->sub($evaluate($value->sub($h)))->div($h);
    }
}
