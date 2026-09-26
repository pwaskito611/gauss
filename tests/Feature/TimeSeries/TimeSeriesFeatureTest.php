<?php

declare(strict_types=1);

namespace Gauss\Tests\Feature\TimeSeries;

use Gauss\Number\Number;
use Gauss\TimeSeries\Model\AR;
use Gauss\TimeSeries\TimeSeries;
use PHPUnit\Framework\TestCase;

final class TimeSeriesFeatureTest extends TestCase
{
    public function testItTransformsFitsAndForecastsAnObservedSeries(): void
    {
        $series = TimeSeries::of([3, 5, 4, 8, 6, 10, 7, 11, 9, 13]);
        $difference = $series->difference();
        $lag = $difference->lag(1);
        $model = AR::fit($series, 2);
        $forecast = $model->predict(2);

        self::assertSame(9, $difference->count());
        self::assertSame(8, $lag->count());
        self::assertInstanceOf(Number::class, $difference->variance());
        self::assertInstanceOf(Number::class, $series->acf(1));
        self::assertSame(8, $model->residuals()->count());
        self::assertSame(12, $forecast->count());
    }
}