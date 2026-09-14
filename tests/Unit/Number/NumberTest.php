<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Number;

use Gauss\Number\Complex;
use Gauss\Number\Irrational;
use Gauss\Number\Number;
use Gauss\Number\Rational;
use Gauss\Number\Real;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class NumberTest extends TestCase
{
    public function testOfIntegerCreatesRational(): void
    {
        $number = Number::of(5);

        self::assertSame(
            Rational::class,
            $number->type()
        );

        self::assertSame('5', $number->value());
    }

    public function testOfNegativeIntegerCreatesRational(): void
    {
        $number = Number::of(-5);

        self::assertSame(
            Rational::class,
            $number->type()
        );

        self::assertSame('-5', $number->value());
    }

    public function testOfFloatCreatesReal(): void
    {
        $number = Number::of(2.5);

        self::assertSame(
            Real::class,
            $number->type()
        );

        self::assertSame('2.5', $number->value());
    }

    public function testOfNumericValueReturnsWrappedValue(): void
    {
        $rational = new Rational(1, 2);

        $number = Number::of($rational);

        self::assertSame(
            Rational::class,
            $number->type()
        );

        self::assertSame('1/2', $number->value());
    }

    public function testParseRational(): void
    {
        $number = Number::of('1/2');

        self::assertSame(
            Rational::class,
            $number->type()
        );

        self::assertSame('1/2', $number->value());
    }

    public function testParseNegativeRational(): void
    {
        $number = Number::of('-3/4');

        self::assertSame(
            Rational::class,
            $number->type()
        );

        self::assertSame('-3/4', $number->value());
    }

    public function testParseRationalWithPositiveSigns(): void
    {
        $number = Number::of('+3/+4');

        self::assertSame(
            Rational::class,
            $number->type()
        );

        self::assertSame('3/4', $number->value());
    }

    public function testParseIntegerString(): void
    {
        $number = Number::of('42');

        self::assertSame(
            Rational::class,
            $number->type()
        );

        self::assertSame('42', $number->value());
    }

    public function testParseNegativeIntegerString(): void
    {
        $number = Number::of('-42');

        self::assertSame(
            Rational::class,
            $number->type()
        );

        self::assertSame('-42', $number->value());
    }

    public function testParseDecimal(): void
    {
        $number = Number::of('2.5');

        self::assertSame(
            Real::class,
            $number->type()
        );

        self::assertSame('2.5', $number->value());
    }

    public function testParseNegativeDecimal(): void
    {
        $number = Number::of('-2.5');

        self::assertSame(
            Real::class,
            $number->type()
        );

        self::assertSame('-2.5', $number->value());
    }

    public function testParseComplex(): void
    {
        $number = Number::of('2+3i');

        self::assertSame(
            Complex::class,
            $number->type()
        );

        self::assertSame('2+3i', $number->value());
    }

    public function testParseComplexWithNegativeImaginary(): void
    {
        $number = Number::of('2-3i');

        self::assertSame(
            Complex::class,
            $number->type()
        );

        self::assertSame('2-3i', $number->value());
    }

    public function testParseNegativeComplex(): void
    {
        $number = Number::of('-2+4i');

        self::assertSame(
            Complex::class,
            $number->type()
        );

        self::assertSame('-2+4i', $number->value());
    }

    public function testParseDecimalComplex(): void
    {
        $number = Number::of('2.5+3.5i');

        self::assertSame(
            Complex::class,
            $number->type()
        );

        self::assertSame('2.5+3.5i', $number->value());
    }

    public function testParsePiAsIrrational(): void
    {
        $number = Number::of('pi');

        self::assertSame(
            Irrational::class,
            $number->type()
        );

        self::assertSame('pi', $number->value());
    }

    public function testParseEAsIrrational(): void
    {
        $number = Number::of('e');

        self::assertSame(
            Irrational::class,
            $number->type()
        );

        self::assertSame('e', $number->value());
    }

    public function testParseSqrtExpressionAsIrrational(): void
    {
        $number = Number::of('sqrt(2)');

        self::assertSame(
            Irrational::class,
            $number->type()
        );

        self::assertSame('sqrt(2)', $number->value());
    }

    public function testParseTrimsWhitespace(): void
    {
        $number = Number::of('  1/2  ');

        self::assertSame(
            Rational::class,
            $number->type()
        );

        self::assertSame('1/2', $number->value());
    }

    public function testInvalidStringThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Number::of('hello');
    }

    public function testInvalidExpressionThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Number::of('2x+3');
    }

    public function testAdd(): void
    {
        $result = Number::of(1)
            ->add(2);

        self::assertSame(
            Rational::class,
            $result->type()
        );

        self::assertSame('3', $result->value());
    }

    public function testSubtract(): void
    {
        $result = Number::of(5)
            ->sub(2);

        self::assertSame(
            Rational::class,
            $result->type()
        );

        self::assertSame('3', $result->value());
    }

    public function testMultiply(): void
    {
        $result = Number::of(3)
            ->mul(4);

        self::assertSame(
            Rational::class,
            $result->type()
        );

        self::assertSame('12', $result->value());
    }

    public function testDivide(): void
    {
        $result = Number::of(1)
            ->div(2);

        self::assertSame(
            Rational::class,
            $result->type()
        );

        self::assertSame('1/2', $result->value());
    }

    public function testAddStringRational(): void
    {
        $result = Number::of('1/2')
            ->add('1/2');

        self::assertSame(
            Rational::class,
            $result->type()
        );

        self::assertSame('1', $result->value());
    }

    public function testMultiplyStringRational(): void
    {
        $result = Number::of('2/3')
            ->mul('3/4');

        self::assertSame(
            Rational::class,
            $result->type()
        );

        self::assertSame('1/2', $result->value());
    }

    public function testAddReal(): void
    {
        $result = Number::of('0.5')
            ->add('0.5');

        self::assertSame(
            Real::class,
            $result->type()
        );

        self::assertSame(
            '1.00000000000000000000000000000000000000000000000000',
            $result->value()
        );
    }

    public function testComplexArithmetic(): void
    {
        $result = Number::of('2+3i')
            ->add('1+2i');

        self::assertSame(
            Complex::class,
            $result->type()
        );

        self::assertSame(
            '3.00000000000000000000000000000000000000000000000000+5.00000000000000000000000000000000000000000000000000i',
            $result->value()
        );
    }

    public function testValueReturnsUnderlyingValue(): void
    {
        $number = Number::of('1/2');

        self::assertSame(
            '1/2',
            $number->value()
        );
    }

    public function testTypeReturnsUnderlyingClass(): void
    {
        $number = Number::of('sqrt(2)');

        self::assertSame(
            Irrational::class,
            $number->type()
        );
    }

    public function testToStringReturnsValue(): void
    {
        $number = Number::of('1/2');

        self::assertSame(
            '1/2',
            (string) $number
        );
    }
}