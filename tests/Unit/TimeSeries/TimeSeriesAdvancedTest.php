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
        self::assertSame(3, $lagged->count());
        self::assertSame(0, $lagged->valueAt(0)->compare(Number::of(10)));
        self::assertSame(0, $lagged->valueAt(1)->compare(Number::of(15)));
        self::assertSame(0, $lagged->valueAt(2)->compare(Number::of(20)));

        $difference = $series->difference(1);
        self::assertSame(3, $difference->count());
        self::assertSame(0, $difference->valueAt(0)->compare(Number::of(5)));
        self::assertSame(0, $difference->valueAt(2)->compare(Number::of(5)));
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

        self::assertSame(0, MAE::calculate($actual, $predicted)->compare(Number::of(2)->div(3)));
        self::assertSame(0, MSE::calculate($actual, $predicted)->compare(Number::of(4)->div(3)));
        self::assertLessThanOrEqual(
            0,
            RMSE::calculate($actual, $predicted)
                ->sub('1.15470053837925152901829756100391491129520350254026')
                ->abs()
                ->compare('0.000000000001')
        );
        self::assertLessThanOrEqual(
            0,
            MAPE::calculate($actual, $predicted)
                ->sub(Number::of(200)->div(9))
                ->abs()
                ->compare('0.000000000001')
        );

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

    public function testDifferenceAndLagDoNotIncludeArtificialZeroPadding(): void
    {
        $series = TimeSeries::of([10, 12, 15, 14]);
        $difference = $series->difference();
        $lagged = TimeSeries::of([10, 20, 30, 40])->lag(2);

        self::assertSame(['2', '3', '-1'], $difference->toArray());
        self::assertSame(['10', '20'], $lagged->toArray());
    }

    public function testPacfIsInvariantToConstantShift(): void
    {
        $series = TimeSeries::of([1, 2, 3, 5, 4, 7, 6, 9]);
        $shifted = TimeSeries::of([101, 102, 103, 105, 104, 107, 106, 109]);

        self::assertLessThanOrEqual(0, $series->pacf(2)->sub($shifted->pacf(2))->abs()->compare('0.000000000001'));
    }
}
