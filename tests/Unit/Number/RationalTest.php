<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Number;

use DivisionByZeroError;
use Gauss\Number\Complex;
use Gauss\Number\Irrational;
use Gauss\Number\NumericValue;
use Gauss\Number\Rational;
use Gauss\Number\Real;
use PHPUnit\Framework\TestCase;

final class RationalTest extends TestCase
{
    public function testValueReturnsIntegerWithoutDenominator(): void
    {
        $rational = new Rational(5);

        self::assertSame('5', $rational->value());
    }

    public function testValueReturnsFraction(): void
    {
        $rational = new Rational(3, 4);

        self::assertSame('3/4', $rational->value());
    }

    public function testNumeratorReturnsNormalizedNumerator(): void
    {
        $rational = new Rational(6, 8);

        self::assertSame(3, $rational->numerator());
    }

    public function testDenominatorReturnsNormalizedDenominator(): void
    {
        $rational = new Rational(6, 8);

        self::assertSame(4, $rational->denominator());
    }

    public function testFractionIsReduced(): void
    {
        $rational = new Rational(12, 18);

        self::assertSame(2, $rational->numerator());
        self::assertSame(3, $rational->denominator());
        self::assertSame('2/3', $rational->value());
    }

    public function testNegativeDenominatorIsNormalized(): void
    {
        $rational = new Rational(1, -2);

        self::assertSame(-1, $rational->numerator());
        self::assertSame(2, $rational->denominator());
        self::assertSame('-1/2', $rational->value());
    }

    public function testBothNegativeValuesBecomePositive(): void
    {
        $rational = new Rational(-2, -4);

        self::assertSame(1, $rational->numerator());
        self::assertSame(2, $rational->denominator());
        self::assertSame('1/2', $rational->value());
    }

    public function testZeroIsNormalized(): void
    {
        $rational = new Rational(0, 10);

        self::assertSame(0, $rational->numerator());
        self::assertSame(1, $rational->denominator());
        self::assertSame('0', $rational->value());
    }

    public function testZeroDenominatorThrowsException(): void
    {
        $this->expectException(DivisionByZeroError::class);

        new Rational(1, 0);
    }

    public function testAddRational(): void
    {
        $result = (new Rational(1, 3))
            ->add(new Rational(1, 6));

        self::assertInstanceOf(Rational::class, $result);
        self::assertSame('1/2', $result->value());
    }

    public function testSubtractRational(): void
    {
        $result = (new Rational(3, 4))
            ->sub(new Rational(1, 4));

        self::assertInstanceOf(Rational::class, $result);
        self::assertSame('1/2', $result->value());
    }

    public function testMultiplyRational(): void
    {
        $result = (new Rational(2, 3))
            ->mul(new Rational(3, 4));

        self::assertInstanceOf(Rational::class, $result);
        self::assertSame('1/2', $result->value());
    }

    public function testDivideRational(): void
    {
        $result = (new Rational(2, 3))
            ->div(new Rational(4, 5));

        self::assertInstanceOf(Rational::class, $result);
        self::assertSame('5/6', $result->value());
    }

    public function testDivideByZeroRationalThrowsException(): void
    {
        $this->expectException(DivisionByZeroError::class);

        (new Rational(1, 2))->div(new Rational(0, 1));
    }

    public function testAddRealPromotesToReal(): void
    {
        $result = (new Rational(1, 2))
            ->add(new Real('0.5'));

        self::assertInstanceOf(Real::class, $result);
        self::assertSame(
            '1.00000000000000000000000000000000000000000000000000',
            $result->value()
        );
    }

    public function testSubtractRealPromotesToReal(): void
    {
        $result = (new Rational(3, 2))
            ->sub(new Real('0.5'));

        self::assertInstanceOf(Real::class, $result);
        self::assertSame(
            '1.00000000000000000000000000000000000000000000000000',
            $result->value()
        );
    }

    public function testMultiplyRealPromotesToReal(): void
    {
        $result = (new Rational(2, 3))
            ->mul(new Real('3'));

        self::assertInstanceOf(Real::class, $result);
        self::assertSame(
            '2.00000000000000000000000000000000000000000000000000',
            $result->value()
        );
    }

    public function testDivideRealPromotesToReal(): void
    {
        $result = (new Rational(1, 2))
            ->div(new Real('2'));

        self::assertInstanceOf(Real::class, $result);
        self::assertSame(
            '0.25000000000000000000000000000000000000000000000000',
            $result->value()
        );
    }

    public function testAddIrrationalPromotesToIrrational(): void
    {
        $result = (new Rational(1, 2))
            ->add(new Irrational('sqrt(2)'));

        self::assertInstanceOf(Irrational::class, $result);
        self::assertSame(
            '(1/2) + (sqrt(2))',
            $result->value()
        );
    }

    public function testSubtractIrrationalPromotesToIrrational(): void
    {
        $result = (new Rational(1, 2))
            ->sub(new Irrational('sqrt(2)'));

        self::assertInstanceOf(Irrational::class, $result);
        self::assertSame(
            '(1/2) - (sqrt(2))',
            $result->value()
        );
    }

    public function testMultiplyIrrationalPromotesToIrrational(): void
    {
        $result = (new Rational(1, 2))
            ->mul(new Irrational('sqrt(2)'));
    
        self::assertInstanceOf(Irrational::class, $result);
        self::assertSame(
            '(1/2) * (sqrt(2))',
            $result->value()
        );
    }

    public function testDivideIrrationalPromotesToIrrational(): void
    {
        $result = (new Rational(1, 2))
            ->div(new Irrational('sqrt(2)'));

        self::assertInstanceOf(Irrational::class, $result);
        self::assertSame(
            '(1/2) / (sqrt(2))',
            $result->value()
        );
    }

    public function testAddComplexPromotesToComplex(): void
    {
        $result = (new Rational(1, 2))
            ->add(new Complex('2', '3'));

        self::assertInstanceOf(Complex::class, $result);
        self::assertSame(
            '2.50000000000000000000000000000000000000000000000000+3.00000000000000000000000000000000000000000000000000i',
            $result->value()
        );
    }

    public function testSubtractComplexPromotesToComplex(): void
    {
        $result = (new Rational(1, 2))
            ->sub(new Complex('2', '3'));

        self::assertInstanceOf(Complex::class, $result);
        self::assertSame(
            '-1.50000000000000000000000000000000000000000000000000-3.00000000000000000000000000000000000000000000000000i',
            $result->value()
        );
    }

    public function testMultiplyComplexPromotesToComplex(): void
    {
        $result = (new Rational(1, 2))
            ->mul(new Complex('2', '3'));

        self::assertInstanceOf(Complex::class, $result);
        self::assertSame(
            '1.00000000000000000000000000000000000000000000000000+1.50000000000000000000000000000000000000000000000000i',
            $result->value()
        );
    }

    public function testDivideComplexPromotesToComplex(): void
    {
        $result = (new Rational(1, 2))
            ->div(new Complex('2', '0'));

        self::assertInstanceOf(Complex::class, $result);
        self::assertSame(
            '0.25000000000000000000000000000000000000000000000000+0.00000000000000000000000000000000000000000000000000i',
            $result->value()
        );
    }

    public function testOperationsReturnNumericValue(): void
    {
        $rational = new Rational(1, 2);

        self::assertInstanceOf(
            NumericValue::class,
            $rational->add(new Rational(1, 2))
        );

        self::assertInstanceOf(
            NumericValue::class,
            $rational->sub(new Rational(1, 2))
        );

        self::assertInstanceOf(
            NumericValue::class,
            $rational->mul(new Rational(1, 2))
        );

        self::assertInstanceOf(
            NumericValue::class,
            $rational->div(new Rational(1, 2))
        );
    }
}

