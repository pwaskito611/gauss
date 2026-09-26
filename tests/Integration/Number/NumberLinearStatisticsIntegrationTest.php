<?php

declare(strict_types=1);

namespace Gauss\Tests\Integration\Number;

use Gauss\Linear\LinearSystem;
use Gauss\Linear\Matrix;
use Gauss\Linear\Solution\UniqueSolution;
use Gauss\Linear\Vector;
use Gauss\Number\Number;
use Gauss\Statistics\Statistics;
use PHPUnit\Framework\TestCase;

final class NumberLinearStatisticsIntegrationTest extends TestCase
{
    public function testItPassesNumberBackedLinearSolutionsIntoStatistics(): void
    {
        $rightHandSide = Vector::of(Number::of('5.000000000000000001'), 1);
        $solution = LinearSystem::of(
            Matrix::of([[2, 1], [1, -1]]),
            $rightHandSide,
        )->solve();

        self::assertInstanceOf(UniqueSolution::class, $solution);
        $values = $solution->vector()->values();
        self::assertLessThanOrEqual(
            0,
            Statistics::mean($values)->sub('1.50000000000000000033333333333333333333333333333333')->abs()->compare('0.000000000000000000000000000001'),
        );

        $reconstructed = Matrix::of([[2, 1], [1, -1]])->multiplyVector($solution->vector());
        self::assertLessThanOrEqual(
            0,
            $reconstructed->get(0)->sub($rightHandSide->get(0))->abs()->compare('0.000000000000000000001'),
        );
        self::assertLessThanOrEqual(
            0,
            $reconstructed->get(1)->sub($rightHandSide->get(1))->abs()->compare('0.000000000000000000001'),
        );
    }
}