<?php

declare(strict_types=1);

namespace Gauss\Numerical\Differentiation;

use Gauss\Number\Number;
use InvalidArgumentException;

final class ForwardDifference
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
    ): Number {
        $value = Number::of($x);
        $h = Number::of($step);

        if ($h->compare(0) === 0) {
            throw new InvalidArgumentException('Step size must not be zero.');
        }

        return $function($value->add($h))->sub($function($value))->div($h);
    }
}
