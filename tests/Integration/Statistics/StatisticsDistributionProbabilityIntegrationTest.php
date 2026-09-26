<?php

declare(strict_types=1);

namespace Gauss\Tests\Integration\Statistics;

use Gauss\Distribution\Normal;
use Gauss\Number\Number;
use Gauss\Probability\Probability;
use Gauss\Statistics\Statistics;
use Gauss\TimeSeries\TimeSeries;
use PHPUnit\Framework\TestCase;

final class StatisticsDistributionProbabilityIntegrationTest extends TestCase
{
    public function testItFeedsObservedMeanAndSpreadIntoANormalProbability(): void
    {
        $series = TimeSeries::of([1, 3, 5, 7]);
        $observations = $series->values();
        $mean = Statistics::mean($observations);
        $deviation = Statistics::populationStandardDeviation($observations);
        $distribution = Normal::of($mean, $deviation);
        $probabilityAtMean = $distribution->cdf($mean);

        self::assertSame(0, $distribution->expectation()->compare(4));
        self::assertLessThanOrEqual(0, $distribution->variance()->sub(5)->abs()->compare('0.000000000000000001'));
        self::assertInstanceOf(Probability::class, $probabilityAtMean);
        self::assertSame(0, $probabilityAtMean->compare('0.5'));
        self::assertGreaterThan(0, $distribution->pdf($mean)->compare(0));
    }
}