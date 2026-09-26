<?php

declare(strict_types=1);

namespace Gauss\Optimization\OneDimensional;

use Gauss\Number\Number;
use Gauss\Optimization\OptimizationResult;
use InvalidArgumentException;

final class GoldenSectionSearch
{
    private const PHI_INVERSE = '0.61803398874989484820';

    private function __construct()
    {
    }

    /**
     * @param callable(Number): Number $objective
     */
    public static function minimize(
        callable $objective,
        int|float|string|Number $lower,
        int|float|string|Number $upper,
        int|float|string|Number $tolerance = '0.000001',
        int $maxIterations = 1000,
    ): OptimizationResult {
        return self::optimize($objective, $lower, $upper, $tolerance, $maxIterations, false);
    }

    /**
     * @param callable(Number): Number $objective
     */
    public static function maximize(
        callable $objective,
        int|float|string|Number $lower,
        int|float|string|Number $upper,
        int|float|string|Number $tolerance = '0.000001',
        int $maxIterations = 1000,
    ): OptimizationResult {
        return self::optimize($objective, $lower, $upper, $tolerance, $maxIterations, true);
    }

    /**
     * @param callable(Number): Number $objective
     */
    private static function optimize(
        callable $objective,
        int|float|string|Number $lower,
        int|float|string|Number $upper,
        int|float|string|Number $tolerance,
        int $maxIterations,
        bool $maximize,
    ): OptimizationResult {
        $a = Number::of($lower);
        $b = Number::of($upper);
        $tol = Number::of($tolerance);

        if ($a->compare($b) >= 0) {
            throw new InvalidArgumentException('Golden section search requires a lower bound smaller than the upper bound.');
        }

        if ($tol->compare(0) <= 0) {
            throw new InvalidArgumentException('Tolerance must be positive.');
        }

        if ($maxIterations <= 0) {
            throw new InvalidArgumentException('Maximum iterations must be positive.');
        }

        $phiInverse = Number::of(self::PHI_INVERSE);
        $span = $b->sub($a);
        $c = $b->sub($span->mul($phiInverse));
        $d = $a->add($span->mul($phiInverse));

        $fc = self::evaluate($objective, $c, $maximize);
        $fd = self::evaluate($objective, $d, $maximize);
        $bestPoint = $c;
        $bestValue = $fc;

        if ($fd->compare($bestValue) < 0) {
            $bestPoint = $d;
            $bestValue = $fd;
        }

        for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
            if ($b->sub($a)->abs()->compare($tol) <= 0) {
                return new OptimizationResult($bestPoint, self::originalValue($bestValue, $maximize), $iteration, true);
            }

            if ($fc->compare($fd) <= 0) {
                $b = $d;
                $d = $c;
                $fd = $fc;
                $span = $b->sub($a);
                $c = $b->sub($span->mul($phiInverse));
                $fc = self::evaluate($objective, $c, $maximize);
                if ($fc->compare($bestValue) < 0) {
                    $bestPoint = $c;
                    $bestValue = $fc;
                }
            } else {
                $a = $c;
                $c = $d;
                $fc = $fd;
                $span = $b->sub($a);
                $d = $a->add($span->mul($phiInverse));
                $fd = self::evaluate($objective, $d, $maximize);
                if ($fd->compare($bestValue) < 0) {
                    $bestPoint = $d;
                    $bestValue = $fd;
                }
            }
        }

        return new OptimizationResult(
            $bestPoint,
            self::originalValue($bestValue, $maximize),
            $maxIterations,
            $b->sub($a)->abs()->compare($tol) <= 0,
        );
    }

    /**
     * @param callable(Number): Number $objective
     */
    private static function evaluate(callable $objective, Number $point, bool $maximize): Number
    {
        $value = Number::of($objective($point));

        return $maximize ? $value->mul(-1) : $value;
    }

    private static function originalValue(Number $value, bool $maximize): Number
    {
        return $maximize ? $value->mul(-1) : $value;
    }
}
