<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Numerical;

use Gauss\Number\Number;
use Gauss\Numerical\Differentiation\BackwardDifference;
use Gauss\Numerical\Differentiation\CentralDifference;
use Gauss\Numerical\Differentiation\ForwardDifference;
use Gauss\Numerical\Integration\SimpsonRule;
use Gauss\Numerical\Integration\TrapezoidalRule;
use Gauss\Numerical\Interpolation\LagrangeInterpolation;
use Gauss\Numerical\Interpolation\LinearInterpolation;
use Gauss\Numerical\Root\Bisection;
use Gauss\Numerical\Root\NewtonRaphson;
use Gauss\Numerical\Root\Secant;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

final class NumericalTest extends TestCase
{
    public function testBisectionFindsRoot(): void
    {
        $root = Bisection::solve(
            static fn (Number $x): Number => $x->pow(2)->sub(4),
            0,
            3,
            '0.000001',
            1000,
        );

        self::assertLessThanOrEqual(
            0,
            Number::of($root->value())
                ->sub(2)
                ->abs()
                ->compare('0.000001')
        );
    }

    public function testBisectionRejectsInvalidBracket(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Bisection::solve(
            static fn (Number $x): Number => $x->pow(2)->sub(4),
            3,
            0,
        );
    }

    public function testNewtonRaphsonFindsRoot(): void
    {
        $root = NewtonRaphson::solve(
            static fn (Number $x): Number => $x->pow(2)->sub(4),
            static fn (Number $x): Number => $x->mul(2),
            1.5,
            '0.000001',
            100,
        );

        self::assertLessThan(1e-6, abs((float) $root->value() - 2.0));
    }

    public function testSecantFindsRoot(): void
    {
        $root = Secant::solve(
            static fn (Number $x): Number => $x->pow(2)->sub(4),
            0,
            3,
            '0.000001',
            100,
        );

        self::assertLessThanOrEqual(
            0,
            Number::of($root->value())
                ->sub(2)
                ->abs()
                ->compare('0.000001')
        );
    }

    public function testSecantRejectsIdenticalInitialGuesses(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Secant::solve(
            static fn (Number $x): Number => $x->pow(2)->sub(4),
            2,
            2,
        );
    }

    public function testTrapezoidalRuleIntegratesPolynomial(): void
    {
        $integral = TrapezoidalRule::integrate(
            static fn (Number $x): Number => $x->pow(2),
            0,
            2,
            100,
        );

        self::assertLessThanOrEqual(
            0,
            Number::of($integral->value())
                ->sub(Number::of(8)->div(3))
                ->abs()
                ->compare('0.001')
        );
    }

    public function testSimpsonRuleIntegratesPolynomial(): void
    {
        $integral = SimpsonRule::integrate(
            static fn (Number $x): Number => $x->pow(2),
            0,
            2,
            100,
        );

        self::assertLessThanOrEqual(
            0,
            Number::of($integral->value())
                ->sub(Number::of(8)->div(3))
                ->abs()
                ->compare('0.000000000001')
        );
    }

    public function testSimpsonRuleRejectsOddSubdivisions(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SimpsonRule::integrate(
            static fn (Number $x): Number => $x->pow(2),
            0,
            2,
            3,
        );
    }

    public function testForwardDifferenceApproximatesDerivative(): void
    {
        $derivative = ForwardDifference::approximate(
            static fn (Number $x): Number => $x->pow(2),
            3,
            '0.000001',
        );

        self::assertLessThan(1.1e-6, abs((float) $derivative->value() - 6.0));
    }

    public function testBackwardDifferenceApproximatesDerivative(): void
    {
        $derivative = BackwardDifference::approximate(
            static fn (Number $x): Number => $x->pow(2),
            3,
            '0.000001',
        );

        self::assertLessThan(1.1e-6, abs((float) $derivative->value() - 6.0));
    }

    public function testCentralDifferenceApproximatesDerivative(): void
    {
        $derivative = CentralDifference::approximate(
            static fn (Number $x): Number => $x->pow(2),
            3,
            '0.000001',
        );

        self::assertLessThan(1.1e-6, abs((float) $derivative->value() - 6.0));
    }

    public function testCentralDifferenceRejectsZeroStep(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CentralDifference::approximate(
            static fn (Number $x): Number => $x->pow(2),
            3,
            0,
        );
    }

    public function testLinearInterpolationEvaluatesPoint(): void
    {
        $value = LinearInterpolation::interpolate(
            Number::of(0),
            Number::of(0),
            Number::of(2),
            Number::of(4),
            Number::of(1),
        );

        self::assertSame(0, $value->compare(2));
    }

    public function testLagrangeInterpolationEvaluatesPolynomial(): void
    {
        $value = LagrangeInterpolation::interpolate(
            [
                [Number::of(0), Number::of(0)],
                [Number::of(1), Number::of(1)],
                [Number::of(2), Number::of(4)],
            ],
            Number::of('1.5'),
        );

        self::assertSame(0, $value->compare('2.25'));
    }

    public function testLagrangeInterpolationRejectsDuplicateXValues(): void
    {
        $this->expectException(InvalidArgumentException::class);

        LagrangeInterpolation::interpolate(
            [
                [Number::of(0), Number::of(0)],
                [Number::of(0), Number::of(1)],
            ],
            Number::of(1),
        );
    }
}
