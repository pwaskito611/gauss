<?php

declare(strict_types=1);

namespace Gauss\Optimization\Multidimensional;

use Gauss\Linear\Vector;
use Gauss\Number\Number;
use Gauss\Optimization\Constraint\BoxConstraint;
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
        * @param int|float|string|Number $gradientStep Positive finite-difference step (default: 0.000001).
        * @param int|float|string|Number|null $lineSearchLowerBound Defaults to zero.
        * @param int|float|string|Number|null $lineSearchUpperBound Defaults to 10 times the learning rate; the learning rate acts as the default line-search scale and the explicit bound overrides it when provided.
        * @param BoxConstraint|null $bounds Optional domain bounds for finite differences and line search.
     */
    public static function minimize(
        callable $objective,
        Vector $initial,
        int|float|string|Number $learningRate,
        int|float|string|Number $tolerance = '0.000001',
        int $maxIterations = 1000,
        int|float|string|Number $gradientStep = '0.000001',
        int|float|string|Number|null $lineSearchLowerBound = 0,
        int|float|string|Number|null $lineSearchUpperBound = null,
        ?BoxConstraint $bounds = null,
    ): OptimizationResult {
        return self::optimize(
            $objective,
            $initial,
            $learningRate,
            $tolerance,
            $maxIterations,
            false,
            $gradientStep,
            $lineSearchLowerBound,
            $lineSearchUpperBound,
            $bounds,
        );
    }

    /**
     * @param callable(Vector): Number $objective
        * @param int|float|string|Number $gradientStep Positive finite-difference step (default: 0.000001).
        * @param int|float|string|Number|null $lineSearchLowerBound Defaults to zero.
        * @param int|float|string|Number|null $lineSearchUpperBound Defaults to 10 times the learning rate; the learning rate acts as the default line-search scale and the explicit bound overrides it when provided.
        * @param BoxConstraint|null $bounds Optional domain bounds for finite differences and line search.
     */
    public static function maximize(
        callable $objective,
        Vector $initial,
        int|float|string|Number $learningRate,
        int|float|string|Number $tolerance = '0.000001',
        int $maxIterations = 1000,
        int|float|string|Number $gradientStep = '0.000001',
        int|float|string|Number|null $lineSearchLowerBound = 0,
        int|float|string|Number|null $lineSearchUpperBound = null,
        ?BoxConstraint $bounds = null,
    ): OptimizationResult {
        return self::optimize(
            $objective,
            $initial,
            $learningRate,
            $tolerance,
            $maxIterations,
            true,
            $gradientStep,
            $lineSearchLowerBound,
            $lineSearchUpperBound,
            $bounds,
        );
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
        int|float|string|Number $gradientStep,
        int|float|string|Number|null $lineSearchLowerBound,
        int|float|string|Number|null $lineSearchUpperBound,
        ?BoxConstraint $bounds,
    ): OptimizationResult {
        $rate = Number::of($learningRate);
        $tol = Number::of($tolerance);
        $step = Number::of($gradientStep);
        $lineLower = Number::of($lineSearchLowerBound ?? 0);
        $lineUpper = Number::of($lineSearchUpperBound ?? $rate->mul(10));

        if ($rate->compare(0) <= 0) {
            throw new InvalidArgumentException('Learning rate must be positive.');
        }

        if ($tol->compare(0) <= 0) {
            throw new InvalidArgumentException('Tolerance must be positive.');
        }

        if ($step->compare(0) <= 0) {
            throw new InvalidArgumentException('Gradient step must be positive.');
        }

        if ($lineLower->compare($lineUpper) >= 0) {
            throw new InvalidArgumentException('Line-search lower bound must be smaller than its upper bound.');
        }

        if ($bounds !== null && $bounds->dimension() !== $initial->dimension()) {
            throw new InvalidArgumentException('Initial point and bounds must have the same dimension.');
        }

        if ($bounds !== null && ! $bounds->contains($initial)) {
            throw new InvalidArgumentException('Initial point must be inside the supplied bounds.');
        }

        if ($maxIterations <= 0) {
            throw new InvalidArgumentException('Maximum iterations must be positive.');
        }

        $current = $initial;
        $currentValue = self::evaluateObjective($objective, $current);
        $directionMultiplier = $maximize ? 1 : -1;

        for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
            $gradient = self::gradient($objective, $current, $step, $bounds);
            $gradientNorm = Number::of($gradient->norm());

            if ($gradientNorm->compare($tol) <= 0) {
                return new OptimizationResult($current, $currentValue, $iteration, true);
            }

            $stepDirection = $gradient->scale(Number::of($directionMultiplier));
            [$feasibleLower, $feasibleUpper] = self::feasibleLineInterval(
                $current,
                $stepDirection,
                $lineLower,
                $lineUpper,
                $bounds,
            );

            if ($feasibleLower->compare($feasibleUpper) === 0) {
                return new OptimizationResult($current, $currentValue, $iteration, true);
            }

            $lineObjective = static function (Number $alpha) use ($current, $stepDirection, $objective, $maximize, $bounds): Number {
                $candidate = $current->add($stepDirection->scale($alpha));
                if ($bounds !== null && ! $bounds->contains($candidate)) {
                    throw new InvalidArgumentException('Gradient descent generated a candidate outside the supplied bounds.');
                }

                $value = self::evaluateObjective($objective, $candidate);

                return $maximize ? $value->mul(-1) : $value;
            };

            $lineResult = GoldenSectionSearch::minimize(
                $lineObjective,
                $feasibleLower,
                $feasibleUpper,
                $tol,
                400,
            );

            $next = $current->add($stepDirection->scale($lineResult->point()));
            $nextValue = $maximize ? $lineResult->value()->mul(-1) : $lineResult->value();
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
    private static function gradient(callable $objective, Vector $point, Number $step, ?BoxConstraint $bounds): Vector
    {
        $values = [];

        $baseValues = [];
        foreach (range(0, $point->dimension() - 1) as $dimensionIndex) {
            $baseValues[] = $point->get($dimensionIndex);
        }

        foreach (range(0, $point->dimension() - 1) as $index) {
            $value = $point->get($index);
            $forwardStep = $step;
            $backwardStep = $step;

            if ($bounds !== null) {
                $forwardDistance = $bounds->upper()->get($index)->sub($value);
                $backwardDistance = $value->sub($bounds->lower()->get($index));
                $forwardStep = $forwardDistance->compare($step) < 0 ? $forwardDistance : $step;
                $backwardStep = $backwardDistance->compare($step) < 0 ? $backwardDistance : $step;

                if ($forwardStep->compare(0) > 0 && $backwardStep->compare(0) > 0) {
                    $symmetricStep = $forwardStep->compare($backwardStep) <= 0 ? $forwardStep : $backwardStep;
                    $forwardStep = $symmetricStep;
                    $backwardStep = $symmetricStep;
                }
            }

            if ($forwardStep->compare(0) === 0 && $backwardStep->compare(0) === 0) {
                $values[] = Number::of(0);
                continue;
            }

            $plus = $baseValues;
            $minus = $baseValues;
            $plus[$index] = $value->add($forwardStep);
            $minus[$index] = $value->sub($backwardStep);

            $plusPoint = Vector::of(...$plus);
            $minusPoint = Vector::of(...$minus);
            if ($backwardStep->compare(0) === 0) {
                $values[] = self::evaluateObjective($objective, $plusPoint)
                    ->sub(self::evaluateObjective($objective, $point))
                    ->div($forwardStep);
            } elseif ($forwardStep->compare(0) === 0) {
                $values[] = self::evaluateObjective($objective, $point)
                    ->sub(self::evaluateObjective($objective, $minusPoint))
                    ->div($backwardStep);
            } else {
                $numerator = self::evaluateObjective($objective, $plusPoint)
                    ->sub(self::evaluateObjective($objective, $minusPoint));
                $values[] = $numerator->div($forwardStep->mul(2));
            }
        }

        return Vector::of(...$values);
    }

    /** @return array{Number, Number} */
    private static function feasibleLineInterval(
        Vector $point,
        Vector $direction,
        Number $lower,
        Number $upper,
        ?BoxConstraint $bounds,
    ): array {
        if ($bounds === null) {
            return [$lower, $upper];
        }

        foreach (range(0, $point->dimension() - 1) as $index) {
            $delta = $direction->get($index);
            if ($delta->compare(0) === 0) {
                continue;
            }

            $first = $bounds->lower()->get($index)->sub($point->get($index))->div($delta);
            $second = $bounds->upper()->get($index)->sub($point->get($index))->div($delta);
            $coordinateLower = $first->compare($second) <= 0 ? $first : $second;
            $coordinateUpper = $first->compare($second) >= 0 ? $first : $second;

            if ($coordinateLower->compare($lower) > 0) {
                $lower = $coordinateLower;
            }
            if ($coordinateUpper->compare($upper) < 0) {
                $upper = $coordinateUpper;
            }
        }

        if ($lower->compare($upper) > 0) {
            throw new InvalidArgumentException('Line-search interval contains no point inside the supplied bounds.');
        }

        return [$lower, $upper];
    }

    /**
     * @param callable(Vector): Number $objective
     */
    private static function evaluateObjective(callable $objective, Vector $point): Number
    {
        return Number::of($objective($point));
    }
}
