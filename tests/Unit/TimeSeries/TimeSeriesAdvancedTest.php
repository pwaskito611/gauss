<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\TimeSeries;

use Gauss\Number\Number;
use Gauss\TimeSeries\Metrics\MAE;
use Gauss\TimeSeries\Metrics\MAPE;
use Gauss\TimeSeries\Metrics\MSE;
use Gauss\TimeSeries\Metrics\RMSE;
use Gauss\TimeSeries\Smoothing\MovingAverage;
use Gauss\TimeSeries\Smoothing\SimpleExponentialSmoothing;
use Gauss\TimeSeries\TimeSeries;
use PHPUnit\Framework\TestCase;

final class TimeSeriesAdvancedTest extends TestCase
{
    public function testLagAndDifferencePreserveNumberValues(): void
    {
        $series = TimeSeries::of([10, 15, 20, 25]);

        $lagged = $series->lag(1);
        self::assertSame(0, $lagged->valueAt(0)->compare(Number::of(0)));
        self::assertSame(0, $lagged->valueAt(1)->compare(Number::of(10)));
        self::assertSame(0, $lagged->valueAt(2)->compare(Number::of(15)));

        $difference = $series->difference(1);
        self::assertSame(0, $difference->valueAt(0)->compare(Number::of(0)));
        self::assertSame(0, $difference->valueAt(1)->compare(Number::of(5)));
        self::assertSame(0, $difference->valueAt(2)->compare(Number::of(5)));
        self::assertSame(0, $difference->valueAt(3)->compare(Number::of(5)));
    }

    public function testMovingAverageAndExponentialSmoothingStayNumberBased(): void
    {
        $series = TimeSeries::of([1, 3, 5, 7]);

        $movingAverage = MovingAverage::smooth($series, 2);
        self::assertSame(0, $movingAverage->valueAt(0)->compare(Number::of(1)));
        self::assertSame(0, $movingAverage->valueAt(1)->compare(Number::of(2)));
        self::assertSame(0, $movingAverage->valueAt(2)->compare(Number::of(4)));
        self::assertSame(0, $movingAverage->valueAt(3)->compare(Number::of(6)));

        $ses = SimpleExponentialSmoothing::smooth($series, 0.5);
        self::assertSame(0, $ses->valueAt(0)->compare(Number::of(1)));
        self::assertSame(0, $ses->valueAt(1)->compare(Number::of(2)));
        self::assertSame(0, $ses->valueAt(2)->compare(Number::of('3.5')));
        self::assertSame(0, $ses->valueAt(3)->compare(Number::of('5.25')));
    }

    public function testAcfAndVarianceCaptureCoreStatistics(): void
    {
        $series = TimeSeries::of([1, 2, 3, 4, 5]);

        self::assertSame(0, $series->acf(0)->compare(Number::of(1)));
        self::assertSame(0, $series->variance()->compare(Number::of(2)));
    }

    public function testForecastingMetricsHandleNumberInputs(): void
    {
        $actual = [1, 2, 3];
        $predicted = [1, 2, 5];

        self::assertSame(0, MAE::calculate($actual, $predicted)->compare(Number::of('2/3')));
        self::assertSame(0, MSE::calculate($actual, $predicted)->compare(Number::of('4/3')));
        self::assertSame(0, RMSE::calculate($actual, $predicted)->compare(Number::of('1.15470053837925152901829756100391491129520350254026')));
        self::assertSame(0, MAPE::calculate($actual, $predicted)->compare(Number::of('200/9')));

        $zeroMape = MAPE::calculate([0, 0, 0], [0, 0, 0]);
        self::assertSame(0, $zeroMape->compare(Number::of(0)));
    }

    public function testSeriesConstantAndLagValidationGuardrails(): void
    {
        $constant = TimeSeries::of([4, 4, 4]);
        self::assertTrue($constant->isConstant());

        $this->expectException(\InvalidArgumentException::class);
        $constant->lag(-1);
    }
}
