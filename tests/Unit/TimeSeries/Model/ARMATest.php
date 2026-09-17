<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\TimeSeries\Model;

use Gauss\Number\Number;
use Gauss\TimeSeries\Model\ARMA;
use Gauss\TimeSeries\TimeSeries;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ARMATest extends TestCase
{
    public function testArmaOneOneFitsAndPredicts(): void
    {
        $series = TimeSeries::of([1, 2, 3, 4, 5, 6, 7, 8]);
        $model = ARMA::fit($series, 1, 1);

        self::assertSame(1, $model->arOrder());
        self::assertSame(1, $model->maOrder());
        self::assertNotSame('0.5', $model->arCoefficients()[0]->value());
        self::assertNotSame('0.25', $model->maCoefficients()[0]->value());
        self::assertTrue($model->predict(2)->count() >= 2);
        self::assertNotEmpty($model->residuals()->observations());
    }

    public function testArmaRejectsInvalidOrders(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ARMA::fit(TimeSeries::of([1, 2, 3]), 0, 0);
    }
}
