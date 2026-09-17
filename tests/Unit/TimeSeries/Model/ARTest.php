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

    public function testArRejectsInvalidOrder(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AR::fit(TimeSeries::of([1, 2, 3]), 0);
    }
}
