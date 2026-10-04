<?php

declare(strict_types=1);

namespace Gauss\TimeSeries\Transform;

use Gauss\TimeSeries\TimeSeries;

final class Lag
{
    public static function apply(TimeSeries $series, int $lag): TimeSeries
    {
        return $series->lag($lag);
    }
}
