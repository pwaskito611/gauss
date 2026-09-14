<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Number;

use DivisionByZeroError;
use Gauss\Number\Complex;
use Gauss\Number\NumericValue;
use Gauss\Number\Rational;
use Gauss\Number\Real;
use PHPUnit\Framework\TestCase;

final class ComplexTest extends TestCase
{
    public function testValueReturnsRealNumberWhenImaginaryIsZero(): void
    {
        $complex = new Complex('5', '0');

        self::assertSame('5', $complex->value());
    }

    public function testValueReturnsComplexNumber(): void
    {
        $complex = new Complex('3', '4');

        self::assertSame('3+4i', $complex->value());
    }

    public function testValueReturnsComplexNumberWithNegativeImaginary(): void
    {
        $complex = new Complex('3', '-4');

        self::assertSame('3-4i', $complex->value());
    }

    public function testRealReturnsRealPart(): void
    {
        $complex = new Complex('3', '4');

        self::assertSame('3', $complex->real());
    }

    public function testImaginaryReturnsImaginaryPart(): void
    {
        $complex = new Complex('3', '4');

        self::assertSame('4', $complex->imaginary());
    }

    public function testAddComplex(): void
    {
        $result = (new Complex('3', '4'))
            ->add(new Complex('2', '5'));

        self::assertInstanceOf(Complex::class, $result);
        self::assertSame('5.00000000000000000000000000000000000000000000000000', $result->real());
        self::assertSame('9.00000000000000000000000000000000000000000000000000', $result->imaginary());
    }

    public function testSubtractComplex(): void
    {
        $result = (new Complex('3', '4'))
            ->sub(new Complex('2', '5'));

        self::assertInstanceOf(Complex::class, $result);
        self::assertSame('1.00000000000000000000000000000000000000000000000000', $result->real());
        self::assertSame('-1.00000000000000000000000000000000000000000000000000', $result->imaginary());
    }

    public function testMultiplyComplex(): void
    {
        // (3 + 4i)(2 + 5i) = -14 + 23i
        $result = (new Complex('3', '4'))
            ->mul(new Complex('2', '5'));

        self::assertInstanceOf(Complex::class, $result);
        self::assertSame('-14.00000000000000000000000000000000000000000000000000', $result->real());
        self::assertSame('23.00000000000000000000000000000000000000000000000000', $result->imaginary());
    }

    public function testDivideComplex(): void
    {
        // (3 + 4i) / (1 + 2i) = 2.2 - 0.4i
        $result = (new Complex('3', '4'))
            ->div(new Complex('1', '2'));

        self::assertInstanceOf(Complex::class, $result);
        self::assertSame('2.20000000000000000000000000000000000000000000000000', $result->real());
        self::assertSame('-0.40000000000000000000000000000000000000000000000000', $result->imaginary());
    }

    public function testAddReal(): void
    {
        $result = (new Complex('3', '4'))
            ->add(new Real('2'));

        self::assertInstanceOf(Complex::class, $result);
        self::assertSame('5.00000000000000000000000000000000000000000000000000', $result->real());
        self::assertSame('4.00000000000000000000000000000000000000000000000000', $result->imaginary());
    }

    public function testSubtractReal(): void
    {
        $result = (new Complex('3', '4'))
            ->sub(new Real('2'));

        self::assertInstanceOf(Complex::class, $result);
        self::assertSame('1.00000000000000000000000000000000000000000000000000', $result->real());
        self::assertSame('4.00000000000000000000000000000000000000000000000000', $result->imaginary());
    }

    public function testMultiplyReal(): void
    {
        $result = (new Complex('3', '4'))
            ->mul(new Real('2'));

        self::assertInstanceOf(Complex::class, $result);
        self::assertSame('6.00000000000000000000000000000000000000000000000000', $result->real());
        self::assertSame('8.00000000000000000000000000000000000000000000000000', $result->imaginary());
    }

    public function testDivideReal(): void
    {
        $result = (new Complex('3', '4'))
            ->div(new Real('2'));

        self::assertInstanceOf(Complex::class, $result);
        self::assertSame('1.50000000000000000000000000000000000000000000000000', $result->real());
        self::assertSame('2.00000000000000000000000000000000000000000000000000', $result->imaginary());
    }

    public function testAddRational(): void
    {
        $result = (new Complex('3', '4'))
            ->add(new Rational(1, 2));

        self::assertInstanceOf(Complex::class, $result);
        self::assertSame('3.50000000000000000000000000000000000000000000000000', $result->real());
        self::assertSame('4.00000000000000000000000000000000000000000000000000', $result->imaginary());
    }

    public function testSubtractRational(): void
    {
        $result = (new Complex('3', '4'))
            ->sub(new Rational(1, 2));

        self::assertInstanceOf(Complex::class, $result);
        self::assertSame('2.50000000000000000000000000000000000000000000000000', $result->real());
        self::assertSame('4.00000000000000000000000000000000000000000000000000', $result->imaginary());
    }

    public function testMultiplyRational(): void
    {
        $result = (new Complex('3', '4'))
            ->mul(new Rational(1, 2));

        self::assertInstanceOf(Complex::class, $result);
        self::assertSame('1.50000000000000000000000000000000000000000000000000', $result->real());
        self::assertSame('2.00000000000000000000000000000000000000000000000000', $result->imaginary());
    }

    public function testDivideRational(): void
    {
        $result = (new Complex('3', '4'))
            ->div(new Rational(2));

        self::assertInstanceOf(Complex::class, $result);
        self::assertSame('1.50000000000000000000000000000000000000000000000000', $result->real());
        self::assertSame('2.00000000000000000000000000000000000000000000000000', $result->imaginary());
    }

    public function testDivideByZeroComplexThrowsException(): void
    {
        $this->expectException(DivisionByZeroError::class);

        (new Complex('3', '4'))
            ->div(new Complex('0', '0'));
    }

    public function testDivideByZeroRealThrowsException(): void
    {
        $this->expectException(DivisionByZeroError::class);

        (new Complex('3', '4'))
            ->div(new Real('0'));
    }

    public function testDivideByZeroRationalThrowsException(): void
    {
        $this->expectException(DivisionByZeroError::class);

        (new Complex('3', '4'))
            ->div(new Rational(0));
    }

    public function testOperationsReturnNumericValue(): void
    {
        $complex = new Complex('3', '4');

        self::assertInstanceOf(
            NumericValue::class,
            $complex->add(new Complex('1', '2'))
        );

        self::assertInstanceOf(
            NumericValue::class,
            $complex->sub(new Complex('1', '2'))
        );

        self::assertInstanceOf(
            NumericValue::class,
            $complex->mul(new Complex('1', '2'))
        );

        self::assertInstanceOf(
            NumericValue::class,
            $complex->div(new Complex('1', '2'))
        );
    }
}