<?php

declare(strict_types=1);

namespace Gauss\TimeSeries\Transform;

use Gauss\TimeSeries\TimeSeries;

final class Difference
{
    public static function apply(TimeSeries $series, int $order = 1): TimeSeries
    {
        return $series->difference($order);
    }
}
