<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Number;

use DivisionByZeroError;
use Gauss\Number\Decimal;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DecimalTest extends TestCase
{
    public function testIsValid(): void
    {
        self::assertTrue(Decimal::isValid('0'));
        self::assertTrue(Decimal::isValid('+1.2300'));
        self::assertTrue(Decimal::isValid('.5'));
        self::assertTrue(Decimal::isValid('5.'));
        self::assertTrue(Decimal::isValid('1e-3'));

        self::assertFalse(Decimal::isValid(''));
        self::assertFalse(Decimal::isValid('abc'));
        self::assertFalse(Decimal::isValid('1.2.3'));
        self::assertFalse(Decimal::isValid('1e'));
    }

    public function testNormalize(): void
    {
        self::assertSame('0', Decimal::normalize('-0'));
        self::assertSame('0', Decimal::normalize('-0.000'));
        self::assertSame('1.23', Decimal::normalize('+001.2300'));
        self::assertSame('-1.23', Decimal::normalize('-0001.2300'));
        self::assertSame('1000', Decimal::normalize('1e3'));
        self::assertSame('1230', Decimal::normalize('1.23e3'));
        self::assertSame('123', Decimal::normalize('1.2300e2'));
        self::assertSame('0.0123', Decimal::normalize('1.23e-2'));
        self::assertSame('1.23', Decimal::normalize('123e-2'));
        self::assertSame('0.5', Decimal::normalize('.5'));
        self::assertSame('5', Decimal::normalize('5.'));
    }

    public function testNormalizeRejectsInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Decimal::normalize('abc');
    }

    public function testNormalizeRejectsExponentOutOfRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Decimal::normalize('1e10001');
    }

    public function testFromFloat(): void
    {
        self::assertSame('1.5', Decimal::fromFloat(1.5));
        self::assertSame('0.30000000000000004', Decimal::fromFloat(0.1 + 0.2));
        self::assertSame('0', Decimal::fromFloat(-0.0));
    }

    public function testFromFloatRejectsInfinity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Decimal::fromFloat(INF);
    }

    public function testIsZero(): void
    {
        self::assertTrue(Decimal::isZero('0'));
        self::assertTrue(Decimal::isZero('-0.000'));
        self::assertFalse(Decimal::isZero('0.0001'));
        self::assertFalse(Decimal::isZero('1e-100'));
    }

    public function testRound(): void
    {
        self::assertSame('1.23', Decimal::round('1.234', 2));
        self::assertSame('1.24', Decimal::round('1.235', 2));
        self::assertSame('2.00', Decimal::round('1.999', 2));
        self::assertSame('-1.24', Decimal::round('-1.235', 2));
        self::assertSame('123', Decimal::round('123.45', 0));
        self::assertSame('124', Decimal::round('123.55', 0));
        self::assertSame('1', Decimal::round('0.5', 0));
        self::assertSame('-1', Decimal::round('-0.5', 0));
    }

    public function testRoundRejectsNegativeScale(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Decimal::round('1', -1);
    }

    public function testRoundScaleLimit(): void
    {
        self::assertSame(100002, strlen(Decimal::round('1', 100000)));

        $this->expectException(InvalidArgumentException::class);
        Decimal::round('1', 100001);
    }

    public function testRoundHalfUpBoundariesAtSupportedScales(): void
    {
        $inputs = [
            '1.2344',
            '1.2345',
            '1.2346',
            '-1.2344',
            '-1.2345',
            '-1.2346',
        ];
        $expectedByScale = [
            0 => ['1', '1', '1', '-1', '-1', '-1'],
            1 => ['1.2', '1.2', '1.2', '-1.2', '-1.2', '-1.2'],
            2 => ['1.23', '1.23', '1.23', '-1.23', '-1.23', '-1.23'],
            3 => ['1.234', '1.235', '1.235', '-1.234', '-1.235', '-1.235'],
        ];

        foreach ([0, 1, 2, 3, 10, 50] as $scale) {
            foreach ($inputs as $index => $input) {
                if ($scale <= 3) {
                    $expected = $expectedByScale[$scale][$index];
                } else {
                    $negative = str_starts_with($input, '-');
                    $fraction = substr($input, strpos($input, '.') + 1);
                    $expected = ($negative ? '-' : '') . '1.'
                        . str_pad($fraction, $scale, '0');
                }

                self::assertSame($expected, Decimal::round($input, $scale));
            }
        }
    }

    public function testAddSubMul(): void
    {
        self::assertSame('0.3', Decimal::add('0.1', '0.2'));
        self::assertSame('6.912', Decimal::add('1.234', '5.678'));
        self::assertSame('1001', Decimal::add('1e3', '1'));
        self::assertSame('-1', Decimal::add('-1.5', '0.5'));

        self::assertSame('0.2', Decimal::sub('0.3', '0.1'));
        self::assertSame('-4.444', Decimal::sub('1.234', '5.678'));
        self::assertSame('0.001', Decimal::sub('1', '0.999'));

        self::assertSame('0.02', Decimal::mul('0.1', '0.2'));
        self::assertSame('7.006652', Decimal::mul('1.234', '5.678'));
        self::assertSame('3', Decimal::mul('1.5', '2'));
        self::assertSame('-7', Decimal::mul('-2', '3.5'));
    }

    public function testDiv(): void
    {
        self::assertSame('0.50', Decimal::div('1', '2', 2));
        self::assertSame('0.3333', Decimal::div('1', '3', 4));
        self::assertSame('0.6667', Decimal::div('2', '3', 4));
        self::assertSame('-0.13', Decimal::div('-1', '8', 2));
    }

    public function testDivByZero(): void
    {
        $this->expectException(DivisionByZeroError::class);
        Decimal::div('1', '0', 2);
    }

    public function testDivScaleLimit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Decimal::div('1', '2', 100000);
    }

    public function testAddRejectsOperandScaleAboveLimit(): void
    {
        $value = '0.' . str_repeat('0', 100000) . '1';

        $this->expectException(InvalidArgumentException::class);
        Decimal::add($value, '0');
    }

    public function testMulRejectsResultScaleAboveLimit(): void
    {
        $value = '0.' . str_repeat('0', 50000) . '1';

        $this->expectException(InvalidArgumentException::class);
        Decimal::mul($value, $value);
    }

    public function testCompare(): void
    {
        self::assertSame(0, Decimal::compare('1.0', '1'));
        self::assertSame(1, Decimal::compare('1.001', '1'));
        self::assertSame(-1, Decimal::compare('-2', '-1.5'));
        self::assertSame(1, Decimal::compare('1e-100', '0'));
    }

    public function testScaleOf(): void
    {
        self::assertSame(2, Decimal::scaleOf('1.2300'));
        self::assertSame(0, Decimal::scaleOf('100'));
        self::assertSame(3, Decimal::scaleOf('0.0010'));
        self::assertSame(3, Decimal::scaleOf('1e-3'));
    }

    public function testOrderOf(): void
    {
        self::assertSame(0, Decimal::orderOf('0'));
        self::assertSame(0, Decimal::orderOf('1'));
        self::assertSame(1, Decimal::orderOf('10'));
        self::assertSame(2, Decimal::orderOf('123'));
        self::assertSame(-1, Decimal::orderOf('0.5'));
        self::assertSame(-3, Decimal::orderOf('0.001'));
        self::assertSame(0, Decimal::orderOf('0.000'));
        self::assertSame(2, Decimal::orderOf('-123.45'));
    }
}