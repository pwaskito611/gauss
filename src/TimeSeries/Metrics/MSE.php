<?php

declare(strict_types=1);

namespace Gauss\TimeSeries\Metrics;

use Gauss\Number\Number;
use InvalidArgumentException;

final class MSE
{
    /**
     * @param list<int|float|string|Number> $actual
     * @param list<int|float|string|Number> $predicted
     */
    public static function calculate(array $actual, array $predicted, bool $precision = true): Number
    {
        if ($actual === [] || $predicted === []) {
            throw new InvalidArgumentException('Actual and predicted series must not be empty.');
        }

        if (count($actual) !== count($predicted)) {
            throw new InvalidArgumentException('Actual and predicted series must have the same length.');
        }

        $sum = Number::of(0);
        foreach ($actual as $index => $value) {
            $actualValue = Number::of($value)->withBackend(! $precision);
            $predictedValue = Number::of($predicted[$index])->withBackend(! $precision);
            $difference = $actualValue->sub($predictedValue);
            $sum = $sum->add($difference->pow(2));
        }

        return $sum->div(Number::of(count($actual)));
    }
}
