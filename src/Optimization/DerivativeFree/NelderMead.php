<?php

declare(strict_types=1);

namespace Gauss\Optimization\DerivativeFree;

use Gauss\Linear\Vector;
use Gauss\Number\Number;
use Gauss\Optimization\Constraint\BoxConstraint;
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
        * @param BoxConstraint|null $bounds Rejects any generated point outside these bounds.
     */
    public static function minimize(
        callable $objective,
        array $simplex,
        int|float|string|Number $tolerance = '0.000001',
        int $maxIterations = 500,
        ?BoxConstraint $bounds = null,
        bool $precision = true,
    ): OptimizationResult {
        return self::optimize($objective, $simplex, $tolerance, $maxIterations, false, $bounds, $precision);
    }

    /**
     * @param callable(Vector): Number $objective
     * @param list<Vector> $simplex
        * @param BoxConstraint|null $bounds Rejects any generated point outside these bounds.
     */
    public static function maximize(
        callable $objective,
        array $simplex,
        int|float|string|Number $tolerance = '0.000001',
        int $maxIterations = 500,
        ?BoxConstraint $bounds = null,
        bool $precision = true,
    ): OptimizationResult {
        return self::optimize($objective, $simplex, $tolerance, $maxIterations, true, $bounds, $precision);
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
        ?BoxConstraint $bounds,
        bool $precision,
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

        if (count($simplex) !== $dimension + 1) {
            throw new InvalidArgumentException('Nelder-Mead requires exactly dimension + 1 simplex points.');
        }

        if ($bounds !== null && $bounds->dimension() !== $dimension) {
            throw new InvalidArgumentException('Simplex and bounds must have the same dimension.');
        }

        foreach ($simplex as $index => $point) {
            if ($bounds !== null && ! $bounds->contains($point)) {
                throw new InvalidArgumentException('Every simplex point must be inside the supplied bounds.');
            }

            for ($previousIndex = 0; $previousIndex < $index; $previousIndex++) {
                $previousPoint = $simplex[$previousIndex];
                $identical = true;
                foreach (range(0, $dimension - 1) as $coordinate) {
                    if ($point->get($coordinate)->compare($previousPoint->get($coordinate)) !== 0) {
                        $identical = false;
                        break;
                    }
                }

                if ($identical) {
                    throw new InvalidArgumentException('Simplex vertices must be distinct.');
                }
            }
        }

        $simplex = array_map(
            static fn (Vector $point): Vector => Vector::of(...array_map(
                static fn (Number $value): Number => $value->withBackend(! $precision),
                $point->values(),
            )),
            $simplex
        );
        if ($bounds !== null) {
            $bounds = BoxConstraint::from(
                Vector::of(...array_map(
                    static fn (Number $value): Number => $value->withBackend(! $precision),
                    $bounds->lower()->values(),
                )),
                Vector::of(...array_map(
                    static fn (Number $value): Number => $value->withBackend(! $precision),
                    $bounds->upper()->values(),
                )),
            );
        }

        $tol = Number::of($tolerance)->withBackend(! $precision);
        if ($tol->compare(0) <= 0) {
            throw new InvalidArgumentException('Tolerance must be positive.');
        }

        if ($maxIterations <= 0) {
            throw new InvalidArgumentException('Maximum iterations must be positive.');
        }

        $points = $simplex;
        $values = [];
        foreach ($points as $point) {
            $values[] = self::evaluate($objective, $point, $maximize, $bounds);
        }

        for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
            $worstIndex = self::argmax($values);
            $bestIndex = self::argmin($values);
            $secondWorstIndex = self::secondWorstIndex($values, $worstIndex);
            $centroid = self::centroid($points, $worstIndex);

            $reflection = self::effectiveCandidate(
                $centroid->add($centroid->sub($points[$worstIndex])->scale(Number::of(1))),
                $bounds,
            );
            $reflectionValue = self::evaluate($objective, $reflection, $maximize, $bounds);

            if ($reflectionValue->compare($values[$bestIndex]) < 0) {
                $expansion = self::effectiveCandidate(
                    $centroid->add($reflection->sub($centroid)->scale(Number::of(2))),
                    $bounds,
                );
                $expansionValue = self::evaluate($objective, $expansion, $maximize, $bounds);

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
                $outsideContraction = $reflectionValue->compare($values[$worstIndex]) < 0;
                $contractionBase = $outsideContraction ? $reflection : $points[$worstIndex];
                $contraction = self::effectiveCandidate(
                    $centroid->add($contractionBase->sub($centroid)->scale(Number::of('0.5'))),
                    $bounds,
                );
                $contractionValue = self::evaluate($objective, $contraction, $maximize, $bounds);

                $contractionThreshold = $outsideContraction
                    ? $reflectionValue
                    : $values[$worstIndex];

                if ($contractionValue->compare($contractionThreshold) < 0) {
                    $points[$worstIndex] = $contraction;
                    $values[$worstIndex] = $contractionValue;
                } else {
                    $bestPoint = $points[$bestIndex];
                    foreach ($points as $index => $point) {
                        if ($index === $bestIndex) {
                            continue;
                        }

                        $shrunkPoint = self::effectiveCandidate(
                            $bestPoint->add($point->sub($bestPoint)->scale(Number::of('0.5'))),
                            $bounds,
                        );
                        $points[$index] = $shrunkPoint;
                        $values[$index] = self::evaluate($objective, $shrunkPoint, $maximize, $bounds);
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

                return new OptimizationResult($bestPoint, self::originalValue($bestValue, $maximize), $iteration + 1, true);
            }
        }

        $bestIndex = self::argmin($values);
        return new OptimizationResult($points[$bestIndex], self::originalValue($values[$bestIndex], $maximize), $maxIterations, false);
    }

    /**
     * @param callable(Vector): Number $objective
     */
    private static function evaluate(callable $objective, Vector $point, bool $maximize, ?BoxConstraint $bounds): Number
    {
        if ($bounds !== null && ! $bounds->contains($point)) {
            throw new InvalidArgumentException('Nelder-Mead generated a candidate outside the supplied bounds.');
        }

        $value = Number::of($objective($point));
        $value = $value->withBackend($point->usesFloatBackend());

        return $maximize ? $value->mul(-1) : $value;
    }

    private static function effectiveCandidate(Vector $point, ?BoxConstraint $bounds): Vector
    {
        if ($bounds === null) {
            return $point;
        }

        $values = [];

        foreach (range(0, $point->dimension() - 1) as $index) {
            $value = $point->get($index);
            $lower = $bounds->lower()->get($index);
            $upper = $bounds->upper()->get($index);

            if ($value->compare($lower) < 0) {
                $value = $lower;
            } elseif ($value->compare($upper) > 0) {
                $value = $upper;
            }

            $values[] = $value;
        }

        return Vector::of(...$values);
    }

    private static function originalValue(Number $value, bool $maximize): Number
    {
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
