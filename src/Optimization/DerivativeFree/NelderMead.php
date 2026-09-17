<?php

declare(strict_types=1);

namespace Gauss\Optimization\DerivativeFree;

use Gauss\Linear\Vector;
use Gauss\Number\Number;
use Gauss\Optimization\OptimizationResult;
use InvalidArgumentException;

final class NelderMead
{
    private function __construct()
    {
    }

    /**
     * @param callable(Vector): Number $objective
     * @param list<Vector> $simplex
     */
    public static function minimize(
        callable $objective,
        array $simplex,
        int|float|string|Number $tolerance = '0.000001',
        int $maxIterations = 500,
    ): OptimizationResult {
        return self::optimize($objective, $simplex, $tolerance, $maxIterations, false);
    }

    /**
     * @param callable(Vector): Number $objective
     * @param list<Vector> $simplex
     */
    public static function maximize(
        callable $objective,
        array $simplex,
        int|float|string|Number $tolerance = '0.000001',
        int $maxIterations = 500,
    ): OptimizationResult {
        return self::optimize($objective, $simplex, $tolerance, $maxIterations, true);
    }

    /**
     * @param callable(Vector): Number $objective
     * @param list<Vector> $simplex
     */
    private static function optimize(
        callable $objective,
        array $simplex,
        int|float|string|Number $tolerance,
        int $maxIterations,
        bool $maximize,
    ): OptimizationResult {
        if ($simplex === []) {
            throw new InvalidArgumentException('Simplex must contain at least one point.');
        }

        $dimension = null;
        foreach ($simplex as $point) {
            if (! $point instanceof Vector) {
                throw new InvalidArgumentException('Every simplex point must be a Vector.');
            }

            if ($dimension === null) {
                $dimension = $point->dimension();
            }

            if ($point->dimension() !== $dimension) {
                throw new InvalidArgumentException('All simplex points must share the same dimension.');
            }
        }

        if ($dimension === null || $dimension < 1) {
            throw new InvalidArgumentException('Simplex points must define a valid vector dimension.');
        }

        $tol = Number::of($tolerance);
        if ($tol->compare(0) <= 0) {
            throw new InvalidArgumentException('Tolerance must be positive.');
        }

        if ($maxIterations <= 0) {
            throw new InvalidArgumentException('Maximum iterations must be positive.');
        }

        $points = $simplex;
        $values = [];
        foreach ($points as $point) {
            $values[] = self::evaluate($objective, $point, $maximize);
        }

        for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
            $worstIndex = self::argmax($values);
            $bestIndex = self::argmin($values);
            $secondWorstIndex = self::secondWorstIndex($values, $worstIndex);
            $centroid = self::centroid($points, $worstIndex);

            $reflection = $centroid->add($centroid->sub($points[$worstIndex])->scale(Number::of(1)));
            $reflectionValue = self::evaluate($objective, $reflection, $maximize);

            if ($reflectionValue->compare($values[$bestIndex]) < 0) {
                $expansion = $centroid->add($reflection->sub($centroid)->scale(Number::of(2)));
                $expansionValue = self::evaluate($objective, $expansion, $maximize);

                if ($expansionValue->compare($reflectionValue) < 0) {
                    $points[$worstIndex] = $expansion;
                    $values[$worstIndex] = $expansionValue;
                } else {
                    $points[$worstIndex] = $reflection;
                    $values[$worstIndex] = $reflectionValue;
                }
            } elseif ($reflectionValue->compare($values[$secondWorstIndex]) < 0) {
                $points[$worstIndex] = $reflection;
                $values[$worstIndex] = $reflectionValue;
            } else {
                if ($reflectionValue->compare($values[$worstIndex]) < 0) {
                    $points[$worstIndex] = $reflection;
                    $values[$worstIndex] = $reflectionValue;
                }

                $contraction = $centroid->add($points[$worstIndex]->sub($centroid)->scale(Number::of('0.5')));
                $contractionValue = self::evaluate($objective, $contraction, $maximize);

                if ($contractionValue->compare($values[$worstIndex]) < 0) {
                    $points[$worstIndex] = $contraction;
                    $values[$worstIndex] = $contractionValue;
                } else {
                    $bestPoint = $points[$bestIndex];
                    foreach ($points as $index => $point) {
                        if ($index === $bestIndex) {
                            continue;
                        }

                        $points[$index] = $bestPoint->add($point->sub($bestPoint)->scale(Number::of('0.5')));
                        $values[$index] = self::evaluate($objective, $points[$index], $maximize);
                    }
                }
            }

            $bestIndex = self::argmin($values);
            $spread = Number::of('0');
            foreach ($points as $index => $point) {
                if ($index === $bestIndex) {
                    continue;
                }

                $difference = $point->sub($points[$bestIndex]);
                foreach (range(0, $difference->dimension() - 1) as $dimensionIndex) {
                    $delta = Number::of($difference->get($dimensionIndex))->abs();
                    if ($delta->compare($spread) > 0) {
                        $spread = $delta;
                    }
                }
            }

            if ($spread->compare($tol) <= 0) {
                $bestPoint = $points[$bestIndex];
                $bestValue = $values[$bestIndex];

                return new OptimizationResult($bestPoint, $bestValue, $iteration + 1, true);
            }
        }

        $bestIndex = self::argmin($values);
        return new OptimizationResult($points[$bestIndex], $values[$bestIndex], $maxIterations, false);
    }

    /**
     * @param callable(Vector): Number $objective
     */
    private static function evaluate(callable $objective, Vector $point, bool $maximize): Number
    {
        $value = Number::of($objective($point));

        return $maximize ? $value->mul(-1) : $value;
    }

    /**
     * @param list<Number> $values
     */
    private static function argmin(array $values): int
    {
        $bestIndex = 0;
        foreach ($values as $index => $value) {
            if ($value->compare($values[$bestIndex]) < 0) {
                $bestIndex = $index;
            }
        }

        return $bestIndex;
    }

    /**
     * @param list<Number> $values
     */
    private static function argmax(array $values): int
    {
        $worstIndex = 0;
        foreach ($values as $index => $value) {
            if ($value->compare($values[$worstIndex]) > 0) {
                $worstIndex = $index;
            }
        }

        return $worstIndex;
    }

    /**
     * @param list<Number> $values
     */
    private static function secondWorstIndex(array $values, int $worstIndex): int
    {
        $secondWorstIndex = 0;
        foreach ($values as $index => $value) {
            if ($index === $worstIndex) {
                continue;
            }

            if ($secondWorstIndex === $worstIndex || $value->compare($values[$secondWorstIndex]) >= 0) {
                $secondWorstIndex = $index;
            }
        }

        return $secondWorstIndex;
    }

    /**
     * @param list<Vector> $points
     */
    private static function centroid(array $points, int $worstIndex): Vector
    {
        $count = count($points) - 1;
        $newValues = [];
        foreach (range(0, $points[0]->dimension() - 1) as $index) {
            $sum = Number::of(0);
            foreach ($points as $pointIndex => $point) {
                if ($pointIndex === $worstIndex) {
                    continue;
                }

                $sum = $sum->add($point->get($index));
            }

            $newValues[] = $sum->div($count);
        }

        return Vector::of(...$newValues);
    }
}
