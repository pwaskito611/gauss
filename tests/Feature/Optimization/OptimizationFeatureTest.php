<?php

declare(strict_types=1);

namespace Gauss\Tests\Feature\Optimization;

use Gauss\Number\Number;
use Gauss\Optimization\OneDimensional\GoldenSectionSearch;
use Gauss\Optimization\OptimizationResult;
use PHPUnit\Framework\TestCase;

final class OptimizationFeatureTest extends TestCase
{
    public function testItReturnsAnOptimizationResultForAConsumerObjective(): void
    {
        $objective = static fn (Number $x): Number => $x->sub(3)->pow(2);
        $result = GoldenSectionSearch::minimize($objective, -2, 8, '0.000001');

        self::assertInstanceOf(OptimizationResult::class, $result);
        self::assertTrue($result->converged());
        self::assertLessThanOrEqual(0, $result->point()->sub(3)->abs()->compare('0.00001'));
        self::assertLessThanOrEqual(0, $objective($result->point())->compare('0.000001'));
        self::assertLessThanOrEqual(0, $result->value()->abs()->compare('0.000001'));
    }
}