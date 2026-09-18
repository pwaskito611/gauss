<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Discrete\NumberTheory;

use Gauss\Discrete\NumberTheory\Divisibility;
use Gauss\Discrete\NumberTheory\Factorization;
use Gauss\Discrete\NumberTheory\GCD;
use Gauss\Discrete\NumberTheory\LCM;
use Gauss\Discrete\NumberTheory\Prime;
use Gauss\Number\Number;
use DivisionByZeroError;
use PHPUnit\Framework\TestCase;

final class NumberTheoryTest extends TestCase
{
    public function testGcdAndLcm(): void
    {
        self::assertSame('6', GCD::of(Number::of(48), Number::of(18))->value());
        self::assertSame('12', GCD::of(Number::of(0), Number::of(12))->value());
        self::assertSame('12', LCM::of(Number::of(4), Number::of(6))->value());
        self::assertSame('0', LCM::of(Number::of(0), Number::of(6))->value());
    }

    public function testDivisibilityAndRemainder(): void
    {
        self::assertTrue(Divisibility::isDivisibleBy(Number::of(12), Number::of(3)));
        self::assertFalse(Divisibility::isDivisibleBy(Number::of(12), Number::of(5)));
        self::assertSame('2', Divisibility::remainder(Number::of(14), Number::of(3))->value());
    }

    public function testPrimeDetectionAndFactorization(): void
    {
        self::assertTrue(Prime::isPrime(Number::of(2)));
        self::assertTrue(Prime::isPrime(Number::of(13)));
        self::assertFalse(Prime::isPrime(Number::of(1)));
        self::assertFalse(Prime::isPrime(Number::of(9)));

        $factorization = Factorization::of(Number::of(60));
        self::assertSame('2', $factorization->primeFactors()[0]->value());
        self::assertSame('2', $factorization->exponents()[0]->value());
    }

    public function testDivisionByZeroIsRejected(): void
    {
        $this->expectException(DivisionByZeroError::class);
        Divisibility::isDivisibleBy(Number::of(5), Number::of(0));
    }
}
