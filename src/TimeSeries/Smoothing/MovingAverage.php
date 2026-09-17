<?php

declare(strict_types=1);

namespace Gauss\TimeSeries\Smoothing;

use Gauss\Number\Number;
use Gauss\TimeSeries\TimeSeries;
use InvalidArgumentException;

final class MovingAverage
{
    public function __construct(private readonly int $window)
    {
        if ($window < 1) {
            throw new InvalidArgumentException('Moving average window must be at least 1.');
        }
    }

    public static function smooth(TimeSeries $series, int $window): TimeSeries
    {
        return (new self($window))->apply($series);
    }

    public static function of(TimeSeries $series, int $window): TimeSeries
    {
        return self::smooth($series, $window);
    }

    public function apply(TimeSeries $series): TimeSeries
    {
        if ($this->window > $series->count()) {
            throw new InvalidArgumentException('Moving average window cannot exceed the series length.');
        }

        $smoothed = [];
        for ($index = 0; $index < $series->count(); $index++) {
            $start = max(0, $index - $this->window + 1);
            $windowValues = [];
            for ($offset = $start; $offset <= $index; $offset++) {
                $windowValues[] = $series->valueAt($offset);
            }
            $sum = Number::of(0);
            foreach ($windowValues as $value) {
                $sum = $sum->add($value);
            }
            $smoothed[] = $sum->div(Number::of(count($windowValues)));
        }

        return TimeSeries::of($smoothed);
    }
}
