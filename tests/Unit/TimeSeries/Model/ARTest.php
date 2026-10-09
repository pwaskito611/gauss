<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\TimeSeries\Model;

use Gauss\Number\Number;
use Gauss\TimeSeries\Model\AR;
use Gauss\TimeSeries\TimeSeries;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ARTest extends TestCase
{
    public function testArOneFitsAndPredicts(): void
    {
        $series = TimeSeries::of([1, 2, 3, 4, 5, 6]);
        $model = AR::fit($series, 1);

        self::assertSame(1, $model->order());
        self::assertSame(1, count($model->coefficients()));
        self::assertSame(0, $model->coefficients()[0]->compare(Number::of(1)));
        self::assertTrue($model->predict(2)->count() >= 2);
        self::assertNotEmpty($model->residuals()->observations());
    }

    public function testOffPrecisionPropagatesFromModelThroughForecastAndResiduals(): void
    {
        $model = AR::fit(TimeSeries::of([1, 2, 3, 4, 5, 6]), 1)->offPrecision();

        self::assertSame('float', $model->intercept()->backend());
        self::assertSame('float', $model->predict(1)->last()->value()->backend());
        self::assertSame('float', $model->residuals()->first()->value()->backend());
    }

    public function testFitPrecisionFlagSelectsModelBackend(): void
    {
        $series = TimeSeries::of([1, 2, 3, 4, 5, 6]);

        self::assertSame('bcmath', AR::fit($series, 1)->intercept()->backend());
        self::assertSame('float', AR::fit($series, 1, false)->intercept()->backend());
    }

    public function testArDoesNotUsePlaceholderCoefficients(): void
    {
        $seriesA = TimeSeries::of([1, 2, 3, 4, 5, 6]);
        $seriesB = TimeSeries::of([1, 4, 10, 20, 35, 56]);

        $modelA = AR::fit($seriesA, 1);
        $modelB = AR::fit($seriesB, 1);

        self::assertNotSame('0.5', $modelA->coefficients()[0]->value());
        self::assertNotSame('0.5', $modelB->coefficients()[0]->value());
        self::assertNotSame($modelA->coefficients()[0]->value(), $modelB->coefficients()[0]->value());
    }

    public function testArResidualsStartOnlyAfterAllLagsAreAvailable(): void
    {
        $series = TimeSeries::of([1, 4, 2, 8, 3, 9, 0, 7, 2, 5]);
        $model = AR::fit($series, 2);
        $residuals = $model->residuals();

        self::assertSame(8, $residuals->count());
        self::assertSame(
            0,
            $residuals->valueAt(0)->compare(
                $series->valueAt(2)
                    ->sub($model->intercept())
                    ->sub($model->coefficients()[0]->mul($series->valueAt(1)))
                    ->sub($model->coefficients()[1]->mul($series->valueAt(0)))
            ),
        );
    }

    public function testArPredictReturnsOnlyForecastObservations(): void
    {
        $series = TimeSeries::of([1, 2, 3, 4, 5, 6]);
        $model = AR::fit($series, 1);

        self::assertSame(3, $model->predict(3)->count());
    }

    public function testArUsesSeriesMeanWhenRegressionIsSingular(): void
    {
        $series = TimeSeries::of([5, 5, 5, 5, 5, 5]);
        $model = AR::fit($series, 2);

        self::assertSame(0, $model->intercept()->compare(Number::of(5)));
        self::assertSame(2, count($model->coefficients()));
        foreach ($model->coefficients() as $coefficient) {
            self::assertSame(0, $coefficient->compare(Number::of(0)));
        }
    }

    public function testArRejectsInvalidOrder(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AR::fit(TimeSeries::of([1, 2, 3]), 0);
    }
}
