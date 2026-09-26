<?php

declare(strict_types=1);

namespace Gauss\Tests\Feature\Statistics;

use Gauss\Number\Number;
use Gauss\Statistics\Statistics;
use PHPUnit\Framework\TestCase;

final class StatisticsFeatureTest extends TestCase
{
    public function testItReportsPopulationAndSampleSpreadForObservations(): void
    {
        $observations = [Number::of(1), Number::of(2), Number::of(3), Number::of(4)];

        self::assertSame(0, Statistics::mean($observations)->compare('2.5'));
        self::assertSame(0, Statistics::populationVariance($observations)->compare('1.25'));
        self::assertLessThanOrEqual(
            0,
            Statistics::sampleVariance($observations)->sub(Number::of(5)->div(3))->abs()->compare('0.00000000000000000001'),
        );
        self::assertLessThanOrEqual(
            0,
            Statistics::populationStandardDeviation($observations)->pow(2)->sub('1.25')->abs()->compare('0.00000000000000000001'),
        );
    }
}