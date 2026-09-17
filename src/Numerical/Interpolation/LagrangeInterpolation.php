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
    public static function interpolate(array $points, int|float|string|Number $x): Number
    {
        if (count($points) < 2) {
            throw new InvalidArgumentException('Lagrange interpolation requires at least two points.');
        }

        $target = Number::of($x);
        $seenX = [];

        foreach ($points as [$pointX, $pointY]) {
            $xValue = Number::of($pointX);
            $yValue = Number::of($pointY);

            if (isset($seenX[$xValue->value()])) {
                throw new InvalidArgumentException('Duplicate x-values are not allowed in interpolation.');
            }

            $seenX[$xValue->value()] = true;
        }

        $result = Number::of(0);

        foreach ($points as $index => [$pointX, $pointY]) {
            $numerator = Number::of(1);
            $denominator = Number::of(1);

            foreach ($points as $otherIndex => [$otherX]) {
                if ($index === $otherIndex) {
                    continue;
                }

                $xValue = Number::of($pointX);
                $otherValue = Number::of($otherX);

                $numerator = $numerator->mul($target->sub($otherValue));
                $denominator = $denominator->mul($xValue->sub($otherValue));
            }

            $result = $result->add(
                $numerator->div($denominator)->mul(Number::of($pointY))
            );
        }

        return $result;
    }
}
