<?php

declare(strict_types=1);

namespace Gauss\TimeSeries\Metrics;

use Gauss\Number\Number;
use InvalidArgumentException;

final class MAPE
{
    /**
     * @param list<int|float|string|Number> $actual
     * @param list<int|float|string|Number> $predicted
     */
    public static function calculate(array $actual, array $predicted): Number
    {
        if (count($actual) !== count($predicted)) {
            throw new InvalidArgumentException('Actual and predicted series must have the same length.');
        }

        $sum = Number::of(0);
        $count = 0;
        foreach ($actual as $index => $value) {
            $actualValue = Number::of($value);
            $predictedValue = Number::of($predicted[$index]);
            if ($actualValue->compare(Number::of(0)) === 0) {
                continue;
            }
            $difference = $actualValue->sub($predictedValue)->abs()->div($actualValue->abs());
            $sum = $sum->add($difference);
            $count++;
        }

        if ($count === 0) {
            return Number::of(0);
        }

        return $sum->div(Number::of($count))->mul(Number::of(100));
    }
}
