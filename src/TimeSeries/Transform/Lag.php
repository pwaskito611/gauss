<?php

declare(strict_types=1);

namespace Gauss\TimeSeries\Transform;

use Gauss\TimeSeries\TimeSeries;
use InvalidArgumentException;

final class Lag
{
    public static function apply(TimeSeries $series, int $lag): TimeSeries
    {
        if ($lag < 0) {
            throw new InvalidArgumentException('Lag must be non-negative.');
        }

        return $series->lag($lag);
    }
}
