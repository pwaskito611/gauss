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
        bool $precision = true,
    ): Number {
        $x = Number::of($initialGuess)->withBackend(! $precision);
        $tol = Number::of($tolerance)->withBackend(! $precision);

        if ($tol->compare(0) <= 0) {
            throw new InvalidArgumentException('Tolerance must be positive.');
        }

        if ($maxIterations <= 0) {
            throw new InvalidArgumentException('Maximum iterations must be positive.');
        }

        $evaluate = static function (callable $callback, Number $point): Number {
            $value = Number::of($callback($point));
            return $value->withBackend($point->usesFloatBackend());
        };

        for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
            $fx = $evaluate($function, $x);

            if ($fx->compare(0) === 0) {
                return $x;
            }

            $dfx = $evaluate($derivative, $x);

            if ($dfx->compare(0) === 0) {
                throw new LogicException('Newton-Raphson derivative is zero at the current iterate.');
            }

            $step = $fx->div($dfx);
            $next = $x->sub($step);

            if ($step->abs()->compare($tol) <= 0) {
                return $next;
            }

            $x = $next;

            if ($iteration === $maxIterations - 1) {
                $fFinal = $evaluate($function, $x);

                if ($fFinal->compare(0) === 0) {
                    return $x;
                }
            }
        }

        throw new LogicException('Newton-Raphson did not converge within the maximum number of iterations.');
    }
}
