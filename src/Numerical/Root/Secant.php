<?php

declare(strict_types=1);

namespace Gauss\Numerical\Root;

use Gauss\Number\Number;
use InvalidArgumentException;
use LogicException;

final class Secant
{
    private function __construct()
    {
    }

    /**
     * @param callable(Number): Number $function
     */
    public static function solve(
        callable $function,
        int|float|string|Number $firstGuess,
        int|float|string|Number $secondGuess,
        int|float|string|Number $tolerance = '0.000001',
        int $maxIterations = 100,
        bool $precision = true,
    ): Number {
        $x0 = Number::of($firstGuess)->withBackend(! $precision);
        $x1 = Number::of($secondGuess)->withBackend(! $precision);
        $tol = Number::of($tolerance)->withBackend(! $precision);

        if ($x0->compare($x1) === 0) {
            throw new InvalidArgumentException('Secant method requires distinct initial guesses.');
        }

        if ($tol->compare(0) <= 0) {
            throw new InvalidArgumentException('Tolerance must be positive.');
        }

        if ($maxIterations <= 0) {
            throw new InvalidArgumentException('Maximum iterations must be positive.');
        }

        $evaluate = static function (Number $point) use ($function): Number {
            $value = Number::of($function($point));
            return $value->withBackend($point->usesFloatBackend());
        };

        for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
            $f0 = $evaluate($x0);
            $f1 = $evaluate($x1);

            if ($f0->compare(0) === 0) {
                return $x0;
            }

            if ($f1->compare(0) === 0) {
                return $x1;
            }

            $denominator = $f1->sub($f0);

            if ($denominator->compare(0) === 0) {
                throw new LogicException('Secant denominator is zero; the method cannot continue.');
            }

            $step = $f1->mul($x1->sub($x0))->div($denominator);
            $next = $x1->sub($step);

            if ($next->sub($x1)->abs()->compare($tol) <= 0) {
                return $next;
            }

            $x0 = $x1;
            $x1 = $next;

            if ($iteration === $maxIterations - 1) {
                $fFinal = $evaluate($x1);

                if ($fFinal->compare(0) === 0) {
                    return $x1;
                }
            }
        }

        throw new LogicException('Secant method did not converge within the maximum number of iterations.');
    }
}
