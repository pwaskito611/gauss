<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\TimeSeries\Model;

use Gauss\Number\Number;
use Gauss\TimeSeries\Model\MA;
use Gauss\TimeSeries\TimeSeries;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class MATest extends TestCase
{
    public function testMaOneFitsAndPredicts(): void
    {
        $series = TimeSeries::of([1, 2, 3, 4, 5, 6]);
        $model = MA::fit($series, 1);

        self::assertSame(1, $model->order());
        self::assertNotSame('0.5', $model->coefficients()[0]->value());
        self::assertTrue($model->predict(2)->count() >= 2);
        self::assertNotEmpty($model->residuals()->observations());
    }

    public function testMaRejectsInvalidOrder(): void
    {
        $this->expectException(InvalidArgumentException::class);

        MA::fit(TimeSeries::of([1, 2, 3]), 0);
    }

    public function testMaResidualsUseZeroPreSampleResiduals(): void
    {
        $series = TimeSeries::of([2, 5, 1, 8]);
        $model = MA::fit($series, 2);
        $residuals = $model->residuals();

        self::assertSame(4, $residuals->count());
        self::assertSame(0, $residuals->valueAt(0)->compare($series->valueAt(0)->sub($model->mean())));
        self::assertSame(
            0,
            $residuals->valueAt(1)->compare(
                $series->valueAt(1)
                    ->sub($model->mean())
                    ->sub($model->coefficients()[0]->mul($residuals->valueAt(0)))
            ),
        );
    }

    public function testMaFitUsesPreviousIterationResidualSnapshot(): void
    {
        $model = MA::fit(TimeSeries::of([2, 5, 1, 8, 3, 9, 0, 7]), 1);

        self::assertLessThanOrEqual(
            0,
            $model->coefficients()[0]
                ->sub('-0.8639756694501398453426800092')
                ->abs()
                ->compare('0.000000000001'),
        );
    }

    public function testMaFitCompletesOnDifficultSeriesWithinItsIterationLimit(): void
    {
        $series = TimeSeries::of([
            100, -95, 89, -82, 76, -69, 63, -56, 50, -43,
            37, -30, 24, -17, 11, -4, -2, 9, -15, 22,
        ]);
        $model = MA::fit($series, 3);

        self::assertSame(3, $model->order());
        self::assertCount(3, $model->coefficients());
    }
}
