<?php

declare(strict_types=1);

namespace Gauss\Tests\Integration\Algebra;

use Gauss\Algebra\Polynomial;
use Gauss\Linear\LinearSystem;
use Gauss\Linear\Matrix;
use Gauss\Linear\Solution\UniqueSolution;
use Gauss\Linear\Vector;
use Gauss\Number\Number;
use Gauss\Numerical\Root\Bisection;
use Gauss\Optimization\OneDimensional\GoldenSectionSearch;
use PHPUnit\Framework\TestCase;

final class PolynomialNumericalOptimizationIntegrationTest extends TestCase
{
    public function testItRecoversAndOptimizesAPolynomialFromSampledValues(): void
    {
        $fit = LinearSystem::of(
            Matrix::of([[1, 0, 0], [1, 1, 1], [1, 2, 4]]),
            Vector::of(9, 4, 1),
        )->solve();

        self::assertInstanceOf(UniqueSolution::class, $fit);
        $coefficients = $fit->vector()->values();
        $polynomial = Polynomial::of($coefficients);
        $stationaryPoint = Bisection::solve(
            static fn (Number $x): Number => $polynomial->derivative()->evaluate($x),
            0,
            6,
            '0.000001',
        );
        $minimum = GoldenSectionSearch::minimize(
            static fn (Number $x): Number => $polynomial->evaluate($x),
            0,
            6,
            '0.000001',
        );

        self::assertSame(0, $polynomial->coefficient(0)->compare(9));
        self::assertSame(0, $polynomial->coefficient(1)->compare(-6));
        self::assertSame(0, $polynomial->coefficient(2)->compare(1));
        self::assertLessThanOrEqual(0, $stationaryPoint->sub(3)->abs()->compare('0.000001'));
        self::assertLessThanOrEqual(0, $minimum->point()->sub(3)->abs()->compare('0.00001'));
        self::assertLessThanOrEqual(0, $minimum->value()->abs()->compare('0.000001'));
    }
}