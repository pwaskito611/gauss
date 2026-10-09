<?php

declare(strict_types=1);

namespace Gauss\Numerical\Interpolation;

use Gauss\Number\Number;
use InvalidArgumentException;

final class LinearInterpolation
{
    private function __construct()
    {
    }

    public static function interpolate(
        int|float|string|Number $x0,
        int|float|string|Number $y0,
        int|float|string|Number $x1,
        int|float|string|Number $y1,
        int|float|string|Number $x,
        bool $precision = true,
    ): Number {
        $backend = ! $precision;
        $x0Value = Number::of($x0)->withBackend($backend);
        $y0Value = Number::of($y0)->withBackend($backend);
        $x1Value = Number::of($x1)->withBackend($backend);
        $y1Value = Number::of($y1)->withBackend($backend);
        $xValue = Number::of($x)->withBackend($backend);

        if ($x0Value->compare($x1Value) === 0) {
            throw new InvalidArgumentException('Linear interpolation requires distinct x values.');
        }

        $slope = $y1Value->sub($y0Value)->div($x1Value->sub($x0Value));

        return $y0Value->add(
            $slope->mul($xValue->sub($x0Value))
        );
    }
}
