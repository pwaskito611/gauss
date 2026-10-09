<?php

declare(strict_types=1);

namespace Gauss\Numerical\Interpolation;

use Gauss\Number\Number;
use InvalidArgumentException;

final class LagrangeInterpolation
{
    private function __construct()
    {
    }

    /**
     * @param array<array{0: int|float|string|Number, 1: int|float|string|Number}> $points
     */
    public static function interpolate(
        array $points,
        int|float|string|Number $x,
        bool $precision = true,
    ): Number
    {
        if (count($points) < 2) {
            throw new InvalidArgumentException('Lagrange interpolation requires at least two points.');
        }

        $target = Number::of($x)->withBackend(! $precision);
        $seenX = [];
        $normalizedPoints = [];

        foreach ($points as [$pointX, $pointY]) {
            $xValue = Number::of($pointX)->withBackend(! $precision);
            $yValue = Number::of($pointY)->withBackend(! $precision);

            foreach ($seenX as $seenValue) {
                if ($xValue->compare($seenValue) === 0) {
                    throw new InvalidArgumentException('Duplicate x-values are not allowed in interpolation.');
                }
            }

            $seenX[] = $xValue;
            $normalizedPoints[] = [$xValue, $yValue];
        }

        $result = Number::of(0);

        foreach ($normalizedPoints as $index => [$pointX, $pointY]) {
            $numerator = Number::of(1);
            $denominator = Number::of(1);

            foreach ($normalizedPoints as $otherIndex => [$otherX]) {
                if ($index === $otherIndex) {
                    continue;
                }

                $numerator = $numerator->mul($target->sub($otherX));
                $denominator = $denominator->mul($pointX->sub($otherX));
            }

            $result = $result->add(
                $numerator->div($denominator)->mul($pointY)
            );
        }

        return $result;
    }
}
