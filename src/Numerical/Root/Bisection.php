<?php

declare(strict_types=1);

namespace Gauss\Numerical\Root;

use Gauss\Number\Number;
use InvalidArgumentException;
use LogicException;

final class Bisection
{
    private function __construct()
    {
    }

    /**
     * @param callable(Number): Number $function
     */
    public static function solve(
        callable $function,
        int|float|string|Number $lower,
        int|float|string|Number $upper,
        int|float|string|Number $tolerance = '0.000001',
        int $maxIterations = 1000,
    ): Number {
        $a = Number::of($lower);
        $b = Number::of($upper);
        $tol = Number::of($tolerance);

        if ($a->compare($b) >= 0) {
            throw new InvalidArgumentException('Bisection requires a lower bound smaller than the upper bound.');
        }

        if ($tol->compare(0) <= 0) {
            throw new InvalidArgumentException('Tolerance must be positive.');
        }

        if ($maxIterations <= 0) {
            throw new InvalidArgumentException('Maximum iterations must be positive.');
        }

        $fa = $function($a);
        $fb = $function($b);

        if ($fa->compare(0) === 0) {
            return $a;
        }

        if ($fb->compare(0) === 0) {
            return $b;
        }

        if ($fa->mul($fb)->compare(0) > 0) {
            throw new InvalidArgumentException('The provided interval does not bracket a sign change for the function.');
        }

        for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
            $midpoint = $a->add($b)->div(2);
            $fMidpoint = $function($midpoint);

            if ($fMidpoint->compare(0) === 0 || $b->sub($a)->abs()->compare($tol) <= 0) {
                return $midpoint;
            }

            if ($fa->mul($fMidpoint)->compare(0) <= 0) {
                $b = $midpoint;
                $fb = $fMidpoint;
            } else {
                $a = $midpoint;
                $fa = $fMidpoint;
            }
        }

        throw new LogicException('Bisection did not converge within the maximum number of iterations.');
    }
}
