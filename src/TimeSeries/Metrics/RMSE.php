<?php

declare(strict_types=1);

namespace Gauss\TimeSeries\Metrics;

use Gauss\Number\Number;

final class RMSE
{
    /**
     * @param list<int|float|string|Number> $actual
     * @param list<int|float|string|Number> $predicted
     */
    public static function calculate(array $actual, array $predicted, bool $precision = true): Number
    {
        return MSE::calculate($actual, $predicted, $precision)->sqrt();
    }
}
