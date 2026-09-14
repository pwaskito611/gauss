<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Algebra;

use Gauss\Algebra\Monomial;
use Gauss\Algebra\Polynomial;
use Gauss\Number\Complex;
use Gauss\Number\Number;
use Gauss\Number\Rational;
use Gauss\Number\Real;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PolynomialTest extends TestCase
{
    public function testFactoriesAndInspection(): void
    {
        $polynomial = Polynomial::of([
            0 => Number::of(1),
            1 => Number::of(2),
            3 => Number::of(4),
        ]);

        self::assertSame(3, $polynomial->degree());
        self::assertSame('2', $polynomial->coefficient(1)->value());
        self::assertSame('0', $polynomial->coefficient(2)->value());
        self::assertSame('4', $polynomial->leadingCoefficient()->value());
        self::assertSame('1', $polynomial->constantTerm()->value());
        self::assertCount(3, $polynomial->terms());
        self::assertInstanceOf(Monomial::class, $polynomial->terms()[0]);
        self::assertFalse($polynomial->isZero());
        self::assertFalse($polynomial->isConstant());
    }

    public function testFactoryNormalizesZeroCoefficients(): void
    {
        $polynomial = Polynomial::of([
            0 => Number::of(0),
            1 => Number::of(2),
            3 => Number::of(0),
        ]);

        self::assertSame([1], array_keys($polynomial->coefficients()));
        self::assertSame('2', $polynomial->coefficient(1)->value());
        self::assertSame(1, $polynomial->degree());
    }

    public function testZeroAndOneFactories(): void
    {
        $zero = Polynomial::zero(Number::of(0));

        self::assertTrue($zero->isZero());
        self::assertSame(0, $zero->degree());
        self::assertTrue(Polynomial::one(Number::of(0))->isMonic());
        self::assertFalse($zero->isMonic());
    }

    public function testConstantZeroAndNegativeDegreeAreHandled(): void
    {
        self::assertTrue(Polynomial::constant(Number::of(0))->isZero());

        $this->expectException(InvalidArgumentException::class);
        Polynomial::of([-1 => Number::of(1)]);
    }

    public function testEmptyFactoryIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Polynomial::of([]);
    }

    public function testArithmetic(): void
    {
        $left = Polynomial::of([0 => Number::of(1), 1 => Number::of(2)]);
        $right = Polynomial::of([0 => Number::of(3), 1 => Number::of(-2), 2 => Number::of(1)]);

        self::assertSame('4', $left->add($right)->constantTerm()->value());
        self::assertSame('-2', $left->sub($right)->constantTerm()->value());
        self::assertSame('3', $left->mul($right)->coefficient(0)->value());
        self::assertSame('-2', $left->negate()->coefficient(1)->value());
        self::assertSame('6', $left->scale(Number::of(3))->coefficient(1)->value());
    }

    public function testEvaluationSupportsExactAndComplexValues(): void
    {
        $polynomial = Polynomial::of([0 => Number::of(1), 1 => Number::of(2), 3 => Number::of(1)]);

        self::assertSame('13', $polynomial->evaluate(Number::of(2))->value());
        self::assertSame(Rational::class, $polynomial->evaluate(Number::of('1/2'))->type());

        $complexResult = Polynomial::of([0 => Number::of(1), 2 => Number::of(1)])
            ->evaluate(Number::of('0+1i'));
        self::assertSame(Complex::class, $complexResult->type());
    }

    public function testDerivativeAndIntegral(): void
    {
        $polynomial = Polynomial::of([0 => Number::of(1), 1 => Number::of(2), 2 => Number::of(3)]);

        $derivative = $polynomial->derivative();
        self::assertSame('2', $derivative->coefficient(0)->value());
        self::assertSame('6', $derivative->coefficient(1)->value());

        $integral = $polynomial->integral(Number::of(5));
        self::assertSame('5', $integral->constantTerm()->value());
        self::assertSame('1', $integral->coefficient(1)->value());
        self::assertSame('1', $integral->coefficient(2)->value());
        self::assertSame('1', $integral->coefficient(3)->value());
    }

    public function testRealCoefficientKeepsRealType(): void
    {
        $polynomial = Polynomial::of([0 => Number::of('2.0'), 1 => Number::of('1.5')]);

        self::assertSame(Real::class, $polynomial->evaluate(Number::of('2.0'))->type());
    }

    public function testOperationsCanBeComposedAsPuzzlePieces(): void
    {
        $polynomial = Polynomial::of([
            0 => Number::of(1),
            1 => Number::of(2),
            2 => Number::of(3),
        ]);

        $result = $polynomial
            ->derivative()
            ->scale(Number::of('1/2'))
            ->evaluate(Number::of('2/3'));

        self::assertSame('3', $result->value());
    }

    public function testOperationsDoNotMutateTheOriginal(): void
    {
        $polynomial = Polynomial::of([0 => Number::of(1), 1 => Number::of(2)]);

        $polynomial->derivative();
        $polynomial->scale(Number::of(3));

        self::assertSame('1', $polynomial->constantTerm()->value());
        self::assertSame('2', $polynomial->coefficient(1)->value());
    }
}
