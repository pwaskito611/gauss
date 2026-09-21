<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Number;

use DivisionByZeroError;
use Gauss\Number\Number;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

final class NumberTest extends TestCase
{
    public function testConstants(): void
    {
        self::assertSame(
            '3.14159265358979323846264338327950288419716939937511',
            Number::pi()->value()
        );

        self::assertSame(
            '2.71828182845904523536028747135266249775724709369996',
            Number::e()->value()
        );
    }

    public function testOf(): void
    {
        self::assertSame('123', Number::of(123)->value());
        self::assertSame('1.5', Number::of(1.5)->value());
        self::assertSame('1.23', Number::of('001.2300')->value());

        $number = Number::of('1.23');
        self::assertSame($number, Number::of($number));
    }

    public function testOfRejectsInvalidString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Number::of('abc');
    }

    public function testOfRejectsInfinity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Number::of(INF);
    }

    public function testOne(): void
    {
        self::assertSame('1', Number::of(5)->one()->value());
    }

    public function testAddSubMul(): void
    {
        self::assertSame('0.3', Number::of('0.1')->add('0.2')->value());
        self::assertSame('5.73', Number::of('1.23')->add('4.5')->value());

        self::assertSame('0.001', Number::of('1')->sub('0.999')->value());

        self::assertSame('0.02', Number::of('0.1')->mul('0.2')->value());
        self::assertSame('3', Number::of('1.5')->mul(2)->value());
    }

    public function testDiv(): void
    {
        self::assertSame('0.5', Number::of(1)->div(2)->value());
        self::assertSame('0.125', Number::of(1)->div(8)->value());
        self::assertSame(
            '0.' . str_repeat('3', 60),
            Number::of(1)->div(3)->value()
        );
    }

    public function testDivByZero(): void
    {
        $this->expectException(DivisionByZeroError::class);
        Number::of(1)->div(0);
    }

    public function testDivVerySmall(): void
    {
        $expected = '0.' . str_repeat('0', 99) . '1';

        self::assertSame($expected, Number::of(1)->div('1e100')->value());
    }

    public function testMod(): void
    {
        self::assertSame('1', Number::of(10)->mod(3)->value());
        self::assertSame('2', Number::of(-10)->mod(3)->value());
        self::assertSame('1', Number::of(10)->mod(-3)->value());
        self::assertSame('2', Number::of(-10)->mod(-3)->value());
    }

    public function testModRejectsNonInteger(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Number::of('1.5')->mod(1);
    }

    public function testModByZero(): void
    {
        $this->expectException(DivisionByZeroError::class);
        Number::of(1)->mod(0);
    }

    public function testCompare(): void
    {
        self::assertSame(0, Number::of('1.0')->compare('1'));
        self::assertSame(1, Number::of(2)->compare(1));
        self::assertSame(-1, Number::of(1)->compare(2));
        self::assertSame(1, Number::of('1e-100')->compare('0'));
    }

    public function testAbs(): void
    {
        self::assertSame('1.23', Number::of('-1.23')->abs()->value());

        $number = Number::of('1.23');
        self::assertSame($number, $number->abs());
    }

    public function testPow(): void
    {
        self::assertSame('1024', Number::of(2)->pow(10)->value());
        self::assertSame('1', Number::of(2)->pow(0)->value());
        self::assertSame('0.25', Number::of(2)->pow(-2)->value());
        self::assertSame('2.25', Number::of('1.5')->pow(2)->value());
    }

    public function testPowRejectsOutOfRangeExponent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Number::of(2)->pow(10001);
    }

    public function testSqrt(): void
    {
        self::assertSame('2', Number::of(4)->sqrt()->value());

        self::assertSame(
            '1.41421356237309504880168872420969807856967187537695',
            Number::of(2)->sqrt()->value()
        );

        self::assertSame('0', Number::of(0)->sqrt()->value());
    }

    public function testSqrtRejectsNegative(): void
    {
        $this->expectException(LogicException::class);
        Number::of(-1)->sqrt();
    }

    public function testExp(): void
    {
        self::assertSame('1', Number::of(0)->exp()->value());
        self::assertSame(Number::e()->value(), Number::of(1)->exp()->value());

        $expNeg1 = Number::of(-1)->exp();

        self::assertStringStartsWith(
            '0.367879441171442321595523770161460867445811131031',
            $expNeg1->value()
        );

        self::assertSame(1, $expNeg1->compare(0));
        self::assertSame(-1, $expNeg1->compare(1));
    }

    public function testExpRejectsOutOfRangeArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Number::of(10001)->exp();
    }

    public function testExpRejectsNegativeOutOfRangeArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Number::of(-10001)->exp();
    }

    public function testTypeAndStringRepresentation(): void
    {
        $integer = Number::of('123');
        self::assertSame('integer', $integer->type());
        self::assertTrue($integer->isIntegerLike());
        self::assertFalse($integer->isDecimalLike());

        $decimal = Number::of('123.45');
        self::assertSame('decimal', $decimal->type());
        self::assertFalse($decimal->isIntegerLike());
        self::assertTrue($decimal->isDecimalLike());
        self::assertSame('123.45', (string) $decimal);

        $normalized = Number::of('123.00');
        self::assertSame('123', $normalized->value());
        self::assertSame('integer', $normalized->type());
    }
}