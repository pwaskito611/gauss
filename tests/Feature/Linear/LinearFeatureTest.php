<?php

declare(strict_types=1);

namespace Gauss\Tests\Feature\Linear;

use Gauss\Linear\LinearSystem;
use Gauss\Linear\Matrix;
use Gauss\Linear\Solution\UniqueSolution;
use Gauss\Linear\Vector;
use PHPUnit\Framework\TestCase;

final class LinearFeatureTest extends TestCase
{
    public function testItSolvesAndRechecksALinearSystem(): void
    {
        $matrix = Matrix::of([[2, 1], [1, -1]]);
        $rightHandSide = Vector::of(5, 1);
        $solution = LinearSystem::of($matrix, $rightHandSide)->solve();

        self::assertInstanceOf(UniqueSolution::class, $solution);
        $vector = $solution->vector();
        self::assertSame(0, $vector->get(0)->compare(2));
        self::assertSame(0, $vector->get(1)->compare(1));

        $reconstructed = $matrix->multiplyVector($vector);
        self::assertSame(0, $reconstructed->get(0)->compare($rightHandSide->get(0)));
        self::assertSame(0, $reconstructed->get(1)->compare($rightHandSide->get(1)));
    }
}