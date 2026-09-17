<?php

declare(strict_types=1);

namespace Gauss\Optimization\Multidimensional;

use Gauss\Linear\Vector;
use Gauss\Number\Number;
use Gauss\Optimization\OneDimensional\GoldenSectionSearch;
use Gauss\Optimization\OptimizationResult;
use InvalidArgumentException;

final class GradientDescent
{
    private function __construct()
    {
    }

    /**
     * @param callable(Vector): Number $objective
     */
    public static function minimize(
        callable $objective,
        Vector $initial,
        int|float|string|Number $learningRate,
        int|float|string|Number $tolerance = '0.000001',
        int $maxIterations = 1000,
    ): OptimizationResult {
        return self::optimize($objective, $initial, $learningRate, $tolerance, $maxIterations, false);
    }

    /**
     * @param callable(Vector): Number $objective
     */
    public static function maximize(
        callable $objective,
        Vector $initial,
        int|float|string|Number $learningRate,
        int|float|string|Number $tolerance = '0.000001',
        int $maxIterations = 1000,
    ): OptimizationResult {
        return self::optimize($objective, $initial, $learningRate, $tolerance, $maxIterations, true);
    }

    /**
     * @param callable(Vector): Number $objective
     */
    private static function optimize(
        callable $objective,
        Vector $initial,
        int|float|string|Number $learningRate,
        int|float|string|Number $tolerance,
        int $maxIterations,
        bool $maximize,
    ): OptimizationResult {
        $rate = Number::of($learningRate);
        $tol = Number::of($tolerance);

        if ($rate->compare(0) <= 0) {
            throw new InvalidArgumentException('Learning rate must be positive.');
        }

        if ($tol->compare(0) <= 0) {
            throw new InvalidArgumentException('Tolerance must be positive.');
        }

        if ($maxIterations <= 0) {
            throw new InvalidArgumentException('Maximum iterations must be positive.');
        }

        $current = $initial;
        $currentValue = self::evaluateObjective($objective, $current);
        $directionMultiplier = $maximize ? 1 : -1;
        $lineScale = $rate->mul(10);

        for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
            $gradient = self::gradient($objective, $current, Number::of('0.000001'));
            $gradientNorm = Number::of($gradient->norm());

            if ($gradientNorm->compare($tol) <= 0) {
                return new OptimizationResult($current, $currentValue, $iteration, true);
            }

            $stepDirection = $gradient->scale(Number::of($directionMultiplier));
            $lineObjective = static function (Number $alpha) use ($current, $stepDirection, $objective, $maximize): Number {
                $candidate = $current->add($stepDirection->scale($alpha));
                $value = self::evaluateObjective($objective, $candidate);

                return $maximize ? $value->mul(-1) : $value;
            };

            $lineResult = GoldenSectionSearch::minimize(
                $lineObjective,
                0,
                $lineScale,
                $tol,
                400,
            );

            $next = $current->add($stepDirection->scale($lineResult->point()));
            $nextValue = self::evaluateObjective($objective, $next);
            $delta = Number::of($next->distance($current));

            $current = $next;
            $currentValue = $nextValue;

            if ($delta->compare($tol) <= 0) {
                return new OptimizationResult($current, $currentValue, $iteration + 1, true);
            }
        }

        return new OptimizationResult($current, $currentValue, $maxIterations, false);
    }

    /**
     * @param callable(Vector): Number $objective
     */
    private static function gradient(callable $objective, Vector $point, Number $step): Vector
    {
        $values = [];

        foreach (range(0, $point->dimension() - 1) as $index) {
            $plus = [];
            $minus = [];

            foreach (range(0, $point->dimension() - 1) as $dimensionIndex) {
                $value = $point->get($dimensionIndex);
                if ($dimensionIndex === $index) {
                    $plus[] = Number::of($value)->add($step);
                    $minus[] = Number::of($value)->sub($step);
                    continue;
                }

                $plus[] = Number::of($value);
                $minus[] = Number::of($value);
            }

            $plusPoint = Vector::of(...$plus);
            $minusPoint = Vector::of(...$minus);
            $numerator = self::evaluateObjective($objective, $plusPoint)->sub(self::evaluateObjective($objective, $minusPoint));
            $values[] = $numerator->div($step->mul(2));
        }

        return Vector::of(...$values);
    }

    /**
     * @param callable(Vector): Number $objective
     */
    private static function evaluateObjective(callable $objective, Vector $point): Number
    {
        return Number::of($objective($point));
    }
}
