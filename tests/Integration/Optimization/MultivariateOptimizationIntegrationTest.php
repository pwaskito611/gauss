<?php

declare(strict_types=1);

namespace Gauss\Tests\Integration\Optimization;

use Gauss\Linear\Vector;
use Gauss\Number\Number;
use Gauss\Optimization\DerivativeFree\NelderMead;
use Gauss\Optimization\OptimizationResult;
use PHPUnit\Framework\TestCase;

final class MultivariateOptimizationIntegrationTest extends TestCase
{
    public function testItFindsAndReevaluatesAKnownMultivariateMinimum(): void
    {
        $objective = static fn (Vector $point): Number => $point->get(0)->sub(2)->pow(2)
            ->add($point->get(1)->add(1)->pow(2));
        $result = NelderMead::minimize(
            $objective,
            [Vector::of(1, -2), Vector::of(3, -2), Vector::of(1, 0)],
            '0.000001',
            500,
        );

        self::assertInstanceOf(OptimizationResult::class, $result);
        self::assertTrue($result->converged());
        self::assertLessThanOrEqual(0, $result->point()->get(0)->sub(2)->abs()->compare('0.00001'));
        self::assertLessThanOrEqual(0, $result->point()->get(1)->add(1)->abs()->compare('0.00001'));
        self::assertLessThanOrEqual(0, $objective($result->point())->abs()->compare('0.000001'));
        self::assertLessThanOrEqual(0, $result->value()->abs()->compare('0.000001'));
    }
}