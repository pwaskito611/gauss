<?php

declare(strict_types=1);

namespace Gauss\TimeSeries\Smoothing;

use Gauss\Number\Number;
use Gauss\TimeSeries\TimeSeries;
use InvalidArgumentException;

/**
 * Computes a trailing moving average using a partial window at the beginning of the series.
 * For example, with window=3, the first values are averaged over [x0], [x0, x1], [x0, x1, x2].
 */
final class MovingAverage
{
    public function __construct(private readonly int $window)
    {
        if ($window < 1) {
            throw new InvalidArgumentException('Moving average window must be at least 1.');
        }
    }

    public static function smooth(TimeSeries $series, int $window, bool $precision = true): TimeSeries
    {
        $series = TimeSeries::of(array_map(
            static fn (Number $value): Number => $value->withBackend(! $precision),
            $series->values(),
        ));

        return (new self($window))->apply($series);
    }

    public static function of(TimeSeries $series, int $window, bool $precision = true): TimeSeries
    {
        return self::smooth($series, $window, $precision);
    }

    public function apply(TimeSeries $series): TimeSeries
    {
        if ($this->window > $series->count()) {
            throw new InvalidArgumentException('Moving average window cannot exceed the series length.');
        }

        $values = $series->values();
        $smoothed = [];
        $sum = Number::of(0);

        for ($index = 0; $index < count($values); $index++) {
            if ($index >= $this->window) {
                $sum = $sum->sub($values[$index - $this->window]);
            }

            $sum = $sum->add($values[$index]);
            $windowSize = min($this->window, $index + 1);
            $smoothed[] = $sum->div(Number::of($windowSize));
        }

        return TimeSeries::of($smoothed);
    }
}
