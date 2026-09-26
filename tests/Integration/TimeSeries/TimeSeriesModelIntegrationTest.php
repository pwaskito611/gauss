<?php

declare(strict_types=1);

namespace Gauss\Tests\Integration\TimeSeries;

use Gauss\Number\Number;
use Gauss\Statistics\Statistics;
use Gauss\TimeSeries\Model\ARMA;
use Gauss\TimeSeries\TimeSeries;
use PHPUnit\Framework\TestCase;

final class TimeSeriesModelIntegrationTest extends TestCase
{
    public function testItFitsAnalyzesAndForecastsAnArmaSeries(): void
    {
        $values = [];
        $innovations = [];
        $previousValue = Number::of(0);
        $seed = 17;
        for ($index = 0; $index < 30; $index++) {
            $seed = ($seed * 73 + 41) % 997;
            $innovation = Number::of($seed - 498)->div(1000);
            $innovations[] = $innovation;
            $previousInnovation = $index === 0 ? Number::of(0) : $innovations[$index - 1];
            $previousValue = Number::of(2)
                ->add(Number::of('0.5')->mul($previousValue))
                ->add(Number::of('0.2')->mul($previousInnovation))
                ->add($innovation)
                ->round(8);
            $values[] = $previousValue;
        }

        $series = TimeSeries::of($values);
        $mean = Statistics::mean($series->values());
        $model = ARMA::fit($series, 1, 3);
        $residuals = $model->residuals();
        $forecast = $model->predict(2);

        self::assertInstanceOf(Number::class, $mean);
        self::assertSame(27, $residuals->count());
        self::assertInstanceOf(Number::class, Statistics::mean($residuals->values()));
        self::assertSame(32, $forecast->count());
        self::assertInstanceOf(Number::class, $forecast->last()->value());
    }
}