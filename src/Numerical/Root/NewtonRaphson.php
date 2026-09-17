<?php

declare(strict_types=1);

namespace Gauss\Numerical\Root;

use Gauss\Number\Number;
use InvalidArgumentException;
use LogicException;

final class NewtonRaphson
{
    private function __construct()
    {
    }

    /**
     * @param callable(Number): Number $function
     * @param callable(Number): Number $derivative
     */
    public static function solve(
        callable $function,
        callable $derivative,
        int|float|string|Number $initialGuess,
        int|float|string|Number $tolerance = '0.000001',
        int $maxIterations = 100,
    ): Number {
        $x = Number::of($initialGuess);
        $tol = Number::of($tolerance);

        if ($tol->compare(0) <= 0) {
            throw new InvalidArgumentException('Tolerance must be positive.');
        }

        if ($maxIterations <= 0) {
            throw new InvalidArgumentException('Maximum iterations must be positive.');
        }

        for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
            $fx = $function($x);

            if ($fx->compare(0) === 0) {
                return $x;
            }

            $dfx = $derivative($x);

            if ($dfx->compare(0) === 0) {
                throw new LogicException('Newton-Raphson derivative is zero at the current iterate.');
            }

            $step = $fx->div($dfx);
            $next = $x->sub($step);

            if ($next->sub($x)->abs()->compare($tol) <= 0 || $step->abs()->compare($tol) <= 0) {
                return $next;
            }

            $x = $next;
        }

        throw new LogicException('Newton-Raphson did not converge within the maximum number of iterations.');
    }
}
