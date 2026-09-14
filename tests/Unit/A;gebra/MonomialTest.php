<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Algebra;

use Gauss\Algebra\Monomial;
use Gauss\Number\Complex;
use Gauss\Number\Number;
use Gauss\Number\Rational;
use Gauss\Number\Real;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class MonomialTest extends TestCase
{
    // ------------------------------------------------------------------
    // Constructor & accessors
    // ------------------------------------------------------------------

    public function testConstructorStoresCoefficient(): void
    {
        $term = new Monomial(Number::of(3), 2);

        self::assertSame('3', $term->coefficient()->value());
    }

    public function testConstructorStoresDegree(): void
    {
        $term = new Monomial(Number::of(3), 2);

        self::assertSame(2, $term->degree());
    }

    public function testDefaultDegreeIsZero(): void
    {
        $term = new Monomial(Number::of(7));

        self::assertSame(0, $term->degree());
    }

    public function testNegativeDegreeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Monomial(Number::of(1), -1);
    }

    public function testCoefficientIsNumericValue(): void
    {
        $term = new Monomial(Number::of('1/2'), 3);

        self::assertSame(Rational::class, $term->coefficient()->type());
        self::assertSame('1/2', $term->coefficient()->value());
    }

    // ------------------------------------------------------------------
    // evaluate()
    // ------------------------------------------------------------------

    public function testEvaluateConstant(): void
    {
        $term = new Monomial(Number::of(7));

        self::assertSame('7', $term->evaluate(Number::of(4))->value());
    }

    public function testEvaluateIntegerPower(): void
    {
        // 3x^2 at x=4  →  48
        $term = new Monomial(Number::of(3), 2);

        self::assertSame('48', $term->evaluate(Number::of(4))->value());
    }

    public function testEvaluateZeroExponent(): void
    {
        // 5x^0 at x=100  →  5
        $term = new Monomial(Number::of(5), 0);

        self::assertSame('5', $term->evaluate(Number::of(100))->value());
    }

    public function testEvaluateAtZero(): void
    {
        // 3x^2 at x=0  →  0
        $term = new Monomial(Number::of(3), 2);

        self::assertSame('0', $term->evaluate(Number::of(0))->value());
    }

    public function testEvaluateWithNegativeCoefficient(): void
    {
        // -5x^4 at x=2  →  -80
        $term = new Monomial(Number::of(-5), 4);

        self::assertSame('-80', $term->evaluate(Number::of(2))->value());
    }

    public function testEvaluateWithNegativeBase(): void
    {
        // 2x^3 at x=-3  →  -54
        $term = new Monomial(Number::of(2), 3);

        self::assertSame('-54', $term->evaluate(Number::of(-3))->value());
    }

    public function testEvaluateRationalCoefficientStaysExact(): void
    {
        // (1/3)x^2 at x=3  →  3
        $term = new Monomial(Number::of('1/3'), 2);

        $result = $term->evaluate(Number::of(3));

        self::assertSame(Rational::class, $result->type());
        self::assertSame('3', $result->value());
    }

    public function testEvaluateRationalBaseStaysExact(): void
    {
        // 3x^2 at x=1/3  →  1/3
        $term = new Monomial(Number::of(3), 2);

        $result = $term->evaluate(Number::of('1/3'));

        self::assertSame(Rational::class, $result->type());
        self::assertSame('1/3', $result->value());
    }

    public function testEvaluateRealKeepsPrecision(): void
    {
        $term = new Monomial(Number::of('2.0'), 2);

        $result = $term->evaluate(Number::of('1.5'));

        self::assertSame(Real::class, $result->type());
    }

    public function testEvaluateComplex(): void
    {
        // 1 * x^2 at x = 0+1i  →  -1
        $term = new Monomial(Number::of(1), 2);

        $result = $term->evaluate(Number::of('0+1i'));

        self::assertSame(Complex::class, $result->type());
    }

    public function testEvaluateHighDegree(): void
    {
        // 1x^10 at x=2  →  1024
        $term = new Monomial(Number::of(1), 10);

        self::assertSame('1024', $term->evaluate(Number::of(2))->value());
    }

    // ------------------------------------------------------------------
    // add()
    // ------------------------------------------------------------------

    public function testAddSameDegree(): void
    {
        // 3x^2 + 5x^2 = 8x^2
        $a = new Monomial(Number::of(3), 2);
        $b = new Monomial(Number::of(5), 2);

        $sum = $a->add($b);

        self::assertSame('8', $sum->coefficient()->value());
        self::assertSame(2, $sum->degree());
    }

    public function testAddNegativeCoefficient(): void
    {
        // 3x^2 + (-5x^2) = -2x^2
        $a = new Monomial(Number::of(3), 2);
        $b = new Monomial(Number::of(-5), 2);

        $sum = $a->add($b);

        self::assertSame('-2', $sum->coefficient()->value());
        self::assertSame(2, $sum->degree());
    }

    public function testAddRational(): void
    {
        // (1/3)x + (1/3)x + (1/3)x = x
        $a = new Monomial(Number::of('1/3'), 1);
        $b = new Monomial(Number::of('1/3'), 1);
        $c = new Monomial(Number::of('1/3'), 1);

        $sum = $a->add($b)->add($c);

        self::assertSame(Rational::class, $sum->coefficient()->type());
        self::assertSame('1', $sum->coefficient()->value());
    }

    public function testAddDifferentDegreesThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Monomial(Number::of(3), 2))
            ->add(new Monomial(Number::of(5), 3));
    }

    public function testAddIsImmutable(): void
    {
        $a = new Monomial(Number::of(3), 2);
        $b = new Monomial(Number::of(5), 2);

        $a->add($b);

        self::assertSame('3', $a->coefficient()->value());
        self::assertSame('5', $b->coefficient()->value());
    }

    // ------------------------------------------------------------------
    // mul()
    // ------------------------------------------------------------------

    public function testMul(): void
    {
        // 3x^2 * 2x^3 = 6x^5
        $a = new Monomial(Number::of(3), 2);
        $b = new Monomial(Number::of(2), 3);

        $product = $a->mul($b);

        self::assertSame('6', $product->coefficient()->value());
        self::assertSame(5, $product->degree());
    }

    public function testMulConstants(): void
    {
        // 3 * 5 = 15
        $a = new Monomial(Number::of(3));
        $b = new Monomial(Number::of(5));

        $product = $a->mul($b);

        self::assertSame('15', $product->coefficient()->value());
        self::assertSame(0, $product->degree());
    }

    public function testMulRational(): void
    {
        // (2/3)x * (3/4)x = (1/2)x^2
        $a = new Monomial(Number::of('2/3'), 1);
        $b = new Monomial(Number::of('3/4'), 1);

        $product = $a->mul($b);

        self::assertSame(Rational::class, $product->coefficient()->type());
        self::assertSame('1/2', $product->coefficient()->value());
        self::assertSame(2, $product->degree());
    }

    public function testMulNegativeCoefficients(): void
    {
        // (-3)x^2 * (-2)x = 6x^3
        $a = new Monomial(Number::of(-3), 2);
        $b = new Monomial(Number::of(-2), 1);

        $product = $a->mul($b);

        self::assertSame('6', $product->coefficient()->value());
        self::assertSame(3, $product->degree());
    }

    public function testMulIsImmutable(): void
    {
        $a = new Monomial(Number::of(3), 2);
        $b = new Monomial(Number::of(2), 3);

        $a->mul($b);

        self::assertSame('3', $a->coefficient()->value());
        self::assertSame(2, $a->degree());
    }

    // ------------------------------------------------------------------
    // derivative()
    // ------------------------------------------------------------------

    public function testDerivativeQuadratic(): void
    {
        // d/dx (3x^2) = 6x
        $term = new Monomial(Number::of(3), 2);

        $deriv = $term->derivative();

        self::assertSame('6', $deriv->coefficient()->value());
        self::assertSame(1, $deriv->degree());
    }

    public function testDerivativeLinear(): void
    {
        // d/dx (5x) = 5
        $term = new Monomial(Number::of(5), 1);

        $deriv = $term->derivative();

        self::assertSame('5', $deriv->coefficient()->value());
        self::assertSame(0, $deriv->degree());
    }

    public function testDerivativeConstantIsZero(): void
    {
        $term = new Monomial(Number::of(7));

        $deriv = $term->derivative();

        self::assertTrue($deriv->isZero());
        self::assertSame(0, $deriv->degree());
    }

    public function testDerivativeRationalCoefficient(): void
    {
        // d/dx ((1/3)x^3) = x^2
        $term = new Monomial(Number::of('1/3'), 3);

        $deriv = $term->derivative();

        self::assertSame(Rational::class, $deriv->coefficient()->type());
        self::assertSame('1', $deriv->coefficient()->value());
        self::assertSame(2, $deriv->degree());
    }

    public function testDerivativeIsImmutable(): void
    {
        $term = new Monomial(Number::of(3), 2);

        $term->derivative();

        self::assertSame('3', $term->coefficient()->value());
        self::assertSame(2, $term->degree());
    }

    // ------------------------------------------------------------------
    // integral()
    // ------------------------------------------------------------------

    public function testIntegralLinear(): void
    {
        // ∫ 2x dx = x^2
        $term = new Monomial(Number::of(2), 1);

        $integral = $term->integral();

        self::assertSame('1', $integral->coefficient()->value());
        self::assertSame(2, $integral->degree());
    }

    public function testIntegralConstant(): void
    {
        // ∫ 5 dx = 5x
        $term = new Monomial(Number::of(5));

        $integral = $term->integral();

        self::assertSame('5', $integral->coefficient()->value());
        self::assertSame(1, $integral->degree());
    }

    public function testIntegralRational(): void
    {
        // ∫ x^2 dx = (1/3)x^3
        $term = new Monomial(Number::of(1), 2);

        $integral = $term->integral();

        self::assertSame(Rational::class, $integral->coefficient()->type());
        self::assertSame('1/3', $integral->coefficient()->value());
        self::assertSame(3, $integral->degree());
    }

    public function testIntegralIsImmutable(): void
    {
        $term = new Monomial(Number::of(2), 1);

        $term->integral();

        self::assertSame('2', $term->coefficient()->value());
        self::assertSame(1, $term->degree());
    }

    // ------------------------------------------------------------------
    // isConstant() / isZero()
    // ------------------------------------------------------------------

    public function testIsConstantTrue(): void
    {
        self::assertTrue((new Monomial(Number::of(7)))->isConstant());
    }

    public function testIsConstantFalse(): void
    {
        self::assertFalse((new Monomial(Number::of(7), 1))->isConstant());
    }

    public function testIsZeroTrue(): void
    {
        self::assertTrue((new Monomial(Number::of(0)))->isZero());
        self::assertTrue((new Monomial(Number::of(0), 5))->isZero());
    }

    public function testIsZeroFalse(): void
    {
        self::assertFalse((new Monomial(Number::of(1)))->isZero());
    }
}