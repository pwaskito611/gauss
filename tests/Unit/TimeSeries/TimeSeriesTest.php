<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\TimeSeries;

use Gauss\Number\Number;
use Gauss\TimeSeries\Observation;
use Gauss\TimeSeries\TimeSeries;
use PHPUnit\Framework\TestCase;

final class TimeSeriesTest extends TestCase
{
    public function testCreatesSeriesAndTracksObservations(): void
    {
        $series = TimeSeries::of([1, 2, 3, 4]);

        self::assertSame(4, $series->count());
        self::assertSame('1', $series->first()->value()->value());
        self::assertSame('4', $series->last()->value()->value());
        self::assertInstanceOf(Observation::class, $series->observations()[0]);
    }

    public function testSlicesAndMapsSeries(): void
    {
        $series = TimeSeries::of([1, 2, 3, 4, 5]);
        $slice = $series->slice(1, 3);
        $mapped = $series->map(static fn (Number $value): Number => $value->mul(2));

        self::assertSame(3, $slice->count());
        self::assertSame('2', $slice->first()->value()->value());
        self::assertSame('10', $mapped->last()->value()->value());
    }
}
