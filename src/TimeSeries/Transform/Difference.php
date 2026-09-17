<?php

declare(strict_types=1);

namespace Gauss\TimeSeries\Transform;

use Gauss\TimeSeries\TimeSeries;
use InvalidArgumentException;

final class Difference
{
    public static function apply(TimeSeries $series, int $order = 1): TimeSeries
    {
        if ($order < 1) {
            throw new InvalidArgumentException('Difference order must be at least 1.');
        }

        return $series->difference($order);
    }
}
