<?php

declare(strict_types=1);

namespace Gauss\TimeSeries\Transform;

use Gauss\Number\Number;
use Gauss\TimeSeries\TimeSeries;

final class Difference
{
    public static function apply(TimeSeries $series, int $order = 1, bool $precision = true): TimeSeries
    {
        $series = TimeSeries::of(array_map(
            static fn (Number $value): Number => $value->withBackend(! $precision),
            $series->values(),
        ));

        return $series->difference($order);
    }
}
