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

final class IrrationalTest extends TestCase
{
    public function testValueReturnsExpression(): void
    {
        $irrational = new Irrational('sqrt(2)');

        self::assertSame(
            'sqrt(2)',
            $irrational->value()
        );
    }

    public function testAddIrrational(): void
    {
        $result = (new Irrational('sqrt(2)'))
            ->add(new Irrational('sqrt(3)'));

        self::assertInstanceOf(Irrational::class, $result);
        self::assertSame(
            '(sqrt(2)) + (sqrt(3))',
            $result->value()
        );
    }

    public function testSubtractIrrational(): void
    {
        $result = (new Irrational('sqrt(2)'))
            ->sub(new Irrational('sqrt(3)'));

        self::assertInstanceOf(Irrational::class, $result);
        self::assertSame(
            '(sqrt(2)) - (sqrt(3))',
            $result->value()
        );
    }

    public function testMultiplyIrrational(): void
    {
        $result = (new Irrational('sqrt(2)'))
            ->mul(new Irrational('sqrt(3)'));

        self::assertInstanceOf(Irrational::class, $result);
        self::assertSame(
            '(sqrt(2)) * (sqrt(3))',
            $result->value()
        );
    }

    public function testDivideIrrational(): void
    {
        $result = (new Irrational('sqrt(2)'))
            ->div(new Irrational('sqrt(3)'));

        self::assertInstanceOf(Irrational::class, $result);
        self::assertSame(
            '(sqrt(2)) / (sqrt(3))',
            $result->value()
        );
    }

    public function testAddRational(): void
    {
        $result = (new Irrational('sqrt(2)'))
            ->add(new Rational(1, 2));

        self::assertInstanceOf(Irrational::class, $result);
        self::assertSame(
            '(sqrt(2)) + (1/2)',
            $result->value()
        );
    }

    public function testSubtractRational(): void
    {
        $result = (new Irrational('sqrt(2)'))
            ->sub(new Rational(1, 2));

        self::assertInstanceOf(Irrational::class, $result);
        self::assertSame(
            '(sqrt(2)) - (1/2)',
            $result->value()
        );
    }

    public function testMultiplyRational(): void
    {
        $result = (new Irrational('sqrt(2)'))
            ->mul(new Rational(1, 2));

        self::assertInstanceOf(Irrational::class, $result);
        self::assertSame(
            '(sqrt(2)) * (1/2)',
            $result->value()
        );
    }

    public function testDivideRational(): void
    {
        $result = (new Irrational('sqrt(2)'))
            ->div(new Rational(1, 2));

        self::assertInstanceOf(Irrational::class, $result);
        self::assertSame(
            '(sqrt(2)) / (1/2)',
            $result->value()
        );
    }

    public function testAddReal(): void
    {
        $result = (new Irrational('sqrt(2)'))
            ->add(new Real('0.5'));

        self::assertInstanceOf(Irrational::class, $result);
        self::assertSame(
            '(sqrt(2)) + (0.5)',
            $result->value()
        );
    }

    public function testSubtractReal(): void
    {
        $result = (new Irrational('sqrt(2)'))
            ->sub(new Real('0.5'));

        self::assertInstanceOf(Irrational::class, $result);
        self::assertSame(
            '(sqrt(2)) - (0.5)',
            $result->value()
        );
    }

    public function testMultiplyReal(): void
    {
        $result = (new Irrational('sqrt(2)'))
            ->mul(new Real('0.5'));

        self::assertInstanceOf(Irrational::class, $result);
        self::assertSame(
            '(sqrt(2)) * (0.5)',
            $result->value()
        );
    }

    public function testDivideReal(): void
    {
        $result = (new Irrational('sqrt(2)'))
            ->div(new Real('0.5'));

        self::assertInstanceOf(Irrational::class, $result);
        self::assertSame(
            '(sqrt(2)) / (0.5)',
            $result->value()
        );
    }

    public function testAddComplexThrowsException(): void
    {
        $this->expectException(\LogicException::class);

        (new Irrational('sqrt(2)'))
            ->add(new Complex('2', '3'));
    }

    public function testSubtractComplexThrowsException(): void
    {
        $this->expectException(\LogicException::class);

        (new Irrational('sqrt(2)'))
            ->sub(new Complex('2', '3'));
    }

    public function testMultiplyComplexThrowsException(): void
    {
        $this->expectException(\LogicException::class);

        (new Irrational('sqrt(2)'))
            ->mul(new Complex('2', '3'));
    }

    public function testDivideComplexThrowsException(): void
    {
        $this->expectException(\LogicException::class);

        (new Irrational('sqrt(2)'))
            ->div(new Complex('2', '3'));
    }

    public function testDivideByZeroThrowsException(): void
    {
        $this->expectException(DivisionByZeroError::class);

        (new Irrational('sqrt(2)'))
            ->div(new Rational(0));
    }

    public function testOperationsReturnNumericValue(): void
    {
        $irrational = new Irrational('sqrt(2)');

        self::assertInstanceOf(
            NumericValue::class,
            $irrational->add(new Rational(1, 2))
        );

        self::assertInstanceOf(
            NumericValue::class,
            $irrational->sub(new Rational(1, 2))
        );

        self::assertInstanceOf(
            NumericValue::class,
            $irrational->mul(new Rational(1, 2))
        );

        self::assertInstanceOf(
            NumericValue::class,
            $irrational->div(new Rational(1, 2))
        );
    }
}