<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Number;

use DivisionByZeroError;
use Gauss\Number\Complex;
use Gauss\Number\Irrational;
use Gauss\Number\NumericValue;
use Gauss\Number\Real;
use PHPUnit\Framework\TestCase;

final class RealTest extends TestCase
{
    public function testValueReturnsNumber(): void
    {
        $real = new Real('10.5');

        self::assertSame('10.5', $real->value());
    }

    public function testAddReal(): void
    {
        $result = (new Real('10.5'))->add(new Real('2.25'));

        self::assertInstanceOf(Real::class, $result);
        self::assertSame(
            '12.75000000000000000000000000000000000000000000000000',
            $result->value()
        );
    }

    public function testSubtractReal(): void
    {
        $result = (new Real('10.5'))->sub(new Real('2.25'));

        self::assertInstanceOf(Real::class, $result);
        self::assertSame(
            '8.25000000000000000000000000000000000000000000000000',
            $result->value()
        );
    }

    public function testMultiplyReal(): void
    {
        $result = (new Real('10.5'))->mul(new Real('2.25'));

        self::assertInstanceOf(Real::class, $result);
        self::assertSame(
            '23.62500000000000000000000000000000000000000000000000',
            $result->value()
        );
    }

    public function testDivideReal(): void
    {
        $result = (new Real('10'))->div(new Real('4'));

        self::assertInstanceOf(Real::class, $result);
        self::assertSame(
            '2.50000000000000000000000000000000000000000000000000',
            $result->value()
        );
    }

    public function testDivideByZeroThrowsException(): void
    {
        $this->expectException(DivisionByZeroError::class);

        (new Real('10'))->div(new Real('0'));
    }

    public function testAddComplex(): void
    {
        $result = (new Real('10'))->add(new Complex('2', '3'));

        self::assertInstanceOf(Complex::class, $result);
        self::assertSame(
            '12.00000000000000000000000000000000000000000000000000+3.00000000000000000000000000000000000000000000000000i',
            $result->value()
        );
    }

    public function testSubtractComplex(): void
    {
        $result = (new Real('10'))->sub(new Complex('2', '3'));

        self::assertInstanceOf(Complex::class, $result);
        self::assertSame(
            '8.00000000000000000000000000000000000000000000000000-3.00000000000000000000000000000000000000000000000000i',
            $result->value()
        );
    }

    public function testMultiplyComplex(): void
    {
        $result = (new Real('10'))->mul(new Complex('2', '3'));

        self::assertInstanceOf(Complex::class, $result);
        self::assertSame(
            '20.00000000000000000000000000000000000000000000000000+30.00000000000000000000000000000000000000000000000000i',
            $result->value()
        );
    }

    public function testDivideComplex(): void
    {
        $result = (new Real('10'))->div(new Complex('2', '0'));

        self::assertInstanceOf(Complex::class, $result);
        self::assertSame(
            '5.00000000000000000000000000000000000000000000000000+0.00000000000000000000000000000000000000000000000000i',
            $result->value()
        );
    }

    public function testAddIrrational(): void
    {
        $result = (new Real('10'))->add(new Irrational('sqrt(2)'));

        self::assertInstanceOf(Irrational::class, $result);
        self::assertSame('(sqrt(2)) + (10)', $result->value());
    }

    public function testSubtractIrrational(): void
    {
        $result = (new Real('10'))->sub(new Irrational('sqrt(2)'));

        self::assertInstanceOf(Irrational::class, $result);
        self::assertSame('(10) - (sqrt(2))', $result->value());
    }

    public function testMultiplyIrrational(): void
    {
        $result = (new Real('10'))->mul(new Irrational('sqrt(2)'));

        self::assertInstanceOf(Irrational::class, $result);
        self::assertSame('(sqrt(2)) * (10)', $result->value());
    }

    public function testDivideIrrational(): void
    {
        $result = (new Real('10'))->div(new Irrational('sqrt(2)'));

        self::assertInstanceOf(Irrational::class, $result);
        self::assertSame('(10) / (sqrt(2))', $result->value());
    }

    public function testOperationsReturnNumericValue(): void
    {
        $real = new Real('10');

        self::assertInstanceOf(
            NumericValue::class,
            $real->add(new Real('5'))
        );

        self::assertInstanceOf(
            NumericValue::class,
            $real->sub(new Real('5'))
        );

        self::assertInstanceOf(
            NumericValue::class,
            $real->mul(new Real('5'))
        );

        self::assertInstanceOf(
            NumericValue::class,
            $real->div(new Real('5'))
        );
    }
}