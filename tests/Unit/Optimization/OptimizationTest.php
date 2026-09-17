<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Optimization;

use Gauss\Linear\Vector;
use Gauss\Number\Number;
use Gauss\Optimization\Constraint\BoxConstraint;
use Gauss\Optimization\DerivativeFree\NelderMead;
use Gauss\Optimization\Multidimensional\CoordinateDescent;
use Gauss\Optimization\Multidimensional\GradientDescent;
use Gauss\Optimization\OneDimensional\GoldenSectionSearch;
use Gauss\Optimization\OptimizationResult;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

final class OptimizationTest extends TestCase
{
    public function testGoldenSectionSearchMinimizesQuadratic(): void
    {
        $result = GoldenSectionSearch::minimize(
            static fn (Number $x): Number => $x->pow(2),
            -5,
            5,
            '0.000001',
            200,
        );

        self::assertInstanceOf(Number::class, $result->point());
        self::assertTrue($result->converged());
        self::assertLessThanOrEqual(
            0,
            $result->point()->sub(0)->abs()->compare('0.000001')
        );
    }

    public function testGoldenSectionSearchRejectsInvalidInterval(): void
    {
        $this->expectException(InvalidArgumentException::class);

        GoldenSectionSearch::minimize(
            static fn (Number $x): Number => $x->pow(2),
            5,
            -5,
        );
    }

    public function testGradientDescentMinimizesQuadratic(): void
    {
        $result = GradientDescent::minimize(
            static fn (Vector $x): Number => $x->get(0)->pow(2)->add($x->get(1)->pow(2)),
            Vector::of(3, -2),
            '0.1',
            '0.000001',
            1000,
        );

        self::assertInstanceOf(Vector::class, $result->point());
        self::assertTrue($result->converged());
        self::assertLessThanOrEqual(0, $result->point()->get(0)->sub(0)->abs()->compare('0.000001'));
        self::assertLessThanOrEqual(0, $result->point()->get(1)->sub(0)->abs()->compare('0.000001'));
    }

    public function testCoordinateDescentMinimizesQuadratic(): void
    {
        $result = CoordinateDescent::minimize(
            static fn (Vector $x): Number => $x->get(0)->pow(2)->add($x->get(1)->pow(2)),
            Vector::of(4, -3),
            '0.000001',
            200,
        );

        self::assertInstanceOf(Vector::class, $result->point());
        self::assertTrue($result->converged());
        self::assertLessThanOrEqual(0, $result->point()->get(0)->sub(0)->abs()->compare('0.000001'));
        self::assertLessThanOrEqual(0, $result->point()->get(1)->sub(0)->abs()->compare('0.000001'));
    }

    public function testNelderMeadMinimizesQuadratic(): void
    {
        $simplex = [
            Vector::of(2, 2),
            Vector::of(3, 0),
            Vector::of(0, 3),
        ];

        $result = NelderMead::minimize(
            static fn (Vector $x): Number => $x->get(0)->pow(2)->add($x->get(1)->pow(2)),
            $simplex,
            '0.000001',
            500,
        );

        self::assertInstanceOf(Vector::class, $result->point());
        self::assertTrue($result->converged());
        self::assertLessThanOrEqual(0, $result->point()->get(0)->sub(0)->abs()->compare('0.000001'));
        self::assertLessThanOrEqual(0, $result->point()->get(1)->sub(0)->abs()->compare('0.000001'));
    }

    public function testBoxConstraintRejectsInvalidBounds(): void
    {
        $this->expectException(InvalidArgumentException::class);

        BoxConstraint::from(
            Vector::of(5),
            Vector::of(1),
        );
    }
}
