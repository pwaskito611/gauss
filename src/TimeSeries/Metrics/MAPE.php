<?php

declare(strict_types=1);

namespace Gauss\TimeSeries\Metrics;

use Gauss\Number\Number;
use InvalidArgumentException;

/**
 * Computes the mean absolute percentage error.
 * This implementation treats MAPE as undefined when any actual value is zero and raises
 * InvalidArgumentException instead of silently skipping or replacing the zero observation.
 */
final class MAPE
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
            if ($actualValue->compare(Number::of(0)) === 0) {
                throw new InvalidArgumentException('MAPE is undefined when actual values are zero.');
            }
            $difference = $actualValue->sub($predictedValue)->abs()->div($actualValue->abs());
            $sum = $sum->add($difference);
        }

        return $sum->div(Number::of(count($actual)))->mul(Number::of(100));
    }
}
