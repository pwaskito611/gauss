<?php

declare(strict_types=1);

namespace Gauss\TimeSeries\Smoothing;

use Gauss\Number\Number;
use Gauss\TimeSeries\TimeSeries;
use InvalidArgumentException;

final class SimpleExponentialSmoothing
{
    public function __construct(private readonly Number $alpha)
    {
        if ($this->alpha->compare(Number::of(0)) < 0 || $this->alpha->compare(Number::of(1)) > 0) {
            throw new InvalidArgumentException('Alpha must be between 0 and 1 inclusive.');
        }
    }

    public static function smooth(TimeSeries $series, int|float|string|Number $alpha): TimeSeries
    {
        return (new self(Number::of($alpha)))->apply($series);
    }

    public function apply(TimeSeries $series): TimeSeries
    {
        $values = $series->values();
        $smoothed = [];
        $level = $values[0];
        $beta = Number::of(1)->sub($this->alpha);
        $smoothed[] = $level;

        foreach ($values as $index => $value) {
            if ($index === 0) {
                continue;
            }

            $level = $this->alpha->mul($value)->add($beta->mul($level));
            $smoothed[] = $level;
        }

        return TimeSeries::of($smoothed);
    }
}
