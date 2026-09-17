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
}
