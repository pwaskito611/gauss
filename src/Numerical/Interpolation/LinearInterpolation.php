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
    ): Number {
        $x0Value = Number::of($x0);
        $y0Value = Number::of($y0);
        $x1Value = Number::of($x1);
        $y1Value = Number::of($y1);
        $xValue = Number::of($x);

        if ($x0Value->compare($x1Value) === 0) {
            throw new InvalidArgumentException('Linear interpolation requires distinct x values.');
        }

        $slope = $y1Value->sub($y0Value)->div($x1Value->sub($x0Value));

        return $y0Value->add(
            $slope->mul($xValue->sub($x0Value))
        );
    }
}
