<?php

declare(strict_types=1);

namespace Gauss\TimeSeries\Metrics;

use Gauss\Number\Number;
use InvalidArgumentException;

final class MAE
{
    /**
     * @param list<int|float|string|Number> $actual
     * @param list<int|float|string|Number> $predicted
     */
    public static function calculate(array $actual, array $predicted): Number
    {
        if ($actual === [] || $predicted === []) {
            throw new InvalidArgumentException('Actual and predicted series must not be empty.');
        }

        if (count($actual) !== count($predicted)) {
            throw new InvalidArgumentException('Actual and predicted series must have the same length.');
        }

        $sum = Number::of(0);
        foreach ($actual as $index => $value) {
            $difference = Number::of($value)->sub(Number::of($predicted[$index]))->abs();
            $sum = $sum->add($difference);
        }

        return $sum->div(Number::of(count($actual)));
    }
}
