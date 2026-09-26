<?php

declare(strict_types=1);

namespace Gauss\Optimization\Multidimensional;

use Gauss\Linear\Vector;
use Gauss\Number\Number;
use Gauss\Optimization\Constraint\BoxConstraint;
use Gauss\Optimization\OneDimensional\GoldenSectionSearch;
use Gauss\Optimization\OptimizationResult;
use InvalidArgumentException;

final class CoordinateDescent
{
    private function __construct()
    {
    }

    /**
     * @param callable(Vector): Number $objective
        * @param BoxConstraint|null $bounds Required coordinate-wise search bounds.
     */
    public static function minimize(
        callable $objective,
        Vector $initial,
        int|float|string|Number $tolerance = '0.000001',
        int $maxIterations = 1000,
        ?BoxConstraint $bounds = null,
    ): OptimizationResult {
        if ($bounds === null) {
            throw new InvalidArgumentException('Coordinate descent requires explicit search bounds.');
        }

        if ($bounds->dimension() !== $initial->dimension()) {
            throw new InvalidArgumentException('Initial point and bounds must have the same dimension.');
        }

        if (! $bounds->contains($initial)) {
            throw new InvalidArgumentException('Initial point must be inside the supplied bounds.');
        }

        $tol = Number::of($tolerance);

        if ($tol->compare(0) <= 0) {
            throw new InvalidArgumentException('Tolerance must be positive.');
        }

        if ($maxIterations <= 0) {
            throw new InvalidArgumentException('Maximum iterations must be positive.');
        }

        $current = $initial;
        $currentValue = Number::of($objective($current));

        for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
            $previous = $current;
            $previousValue = $currentValue;

            for ($index = 0; $index < $current->dimension(); $index++) {
                $lower = $bounds->lower()->get($index);
                $upper = $bounds->upper()->get($index);

                if ($lower->compare($upper) === 0) {
                    continue;
                }

                $lineObjective = static function (Number $x) use ($current, $objective, $index): Number {
                    $values = [];
                    foreach (range(0, $current->dimension() - 1) as $dimensionIndex) {
                        if ($dimensionIndex === $index) {
                            $values[] = $x;
                            continue;
                        }

                        $values[] = $current->get($dimensionIndex);
                    }

                    return Number::of($objective(Vector::of(...$values)));
                };

                $candidate = GoldenSectionSearch::minimize(
                    $lineObjective,
                    $lower,
                    $upper,
                    $tol,
                    200,
                );

                $updated = [];
                foreach (range(0, $current->dimension() - 1) as $dimensionIndex) {
                    $updated[] = $dimensionIndex === $index ? $candidate->point() : $current->get($dimensionIndex);
                }

                $current = Vector::of(...$updated);
            }

            $currentValue = Number::of($objective($current));
            $delta = Number::of($current->distance($previous));

            if ($delta->compare($tol) <= 0 || $previousValue->sub($currentValue)->abs()->compare($tol) <= 0) {
                return new OptimizationResult($current, $currentValue, $iteration + 1, true);
            }
        }

        return new OptimizationResult($current, $currentValue, $maxIterations, false);
    }
}
