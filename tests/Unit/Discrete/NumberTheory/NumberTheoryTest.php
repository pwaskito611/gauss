<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Discrete\NumberTheory;

use DivisionByZeroError;
use Gauss\Discrete\NumberTheory\Congruence;
use Gauss\Discrete\NumberTheory\Coprime;
use Gauss\Discrete\NumberTheory\Divisibility;
use Gauss\Discrete\NumberTheory\EulerTotient;
use Gauss\Discrete\NumberTheory\ExtendedGCD;
use Gauss\Discrete\NumberTheory\Factorization;
use Gauss\Discrete\NumberTheory\GCD;
use Gauss\Discrete\NumberTheory\IntegerSquareRoot;
use Gauss\Discrete\NumberTheory\LCM;
use Gauss\Discrete\NumberTheory\ModularArithmetic;
use Gauss\Discrete\NumberTheory\ModularInverse;
use Gauss\Discrete\NumberTheory\Prime;
use Gauss\Number\Number;
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

    public function testExtendedGcdAndCoprime(): void
    {
        $extended = ExtendedGCD::of(Number::of(30), Number::of(12));
        self::assertSame('6', $extended->gcd()->value());
        self::assertSame('6', $extended->coefficientX()->mul(Number::of(30))->add($extended->coefficientY()->mul(Number::of(12)))->value());

        self::assertTrue(Coprime::of(Number::of(8), Number::of(15)));
        self::assertFalse(Coprime::of(Number::of(12), Number::of(18)));
        self::assertTrue(Coprime::of(Number::of(-8), Number::of(15)));
        self::assertFalse(Coprime::of(Number::of(0), Number::of(18)));
    }

    public function testDivisibilityAndRemainder(): void
    {
        self::assertTrue(Divisibility::isDivisibleBy(Number::of(12), Number::of(3)));
        self::assertFalse(Divisibility::isDivisibleBy(Number::of(12), Number::of(5)));
        self::assertSame('2', Divisibility::remainder(Number::of(14), Number::of(3))->value());
        self::assertSame('1', Divisibility::remainder(Number::of(7), Number::of(3))->value());
        self::assertSame('2', Divisibility::remainder(Number::of(-7), Number::of(3))->value());
        self::assertSame('1', Divisibility::remainder(Number::of(7), Number::of(-3))->value());
        self::assertSame('2', Divisibility::remainder(Number::of(-7), Number::of(-3))->value());
    }

    public function testPrimeDetectionAndFactorization(): void
    {
        self::assertTrue(Prime::isPrime(Number::of(2)));
        self::assertTrue(Prime::isPrime(Number::of(13)));
        self::assertFalse(Prime::isPrime(Number::of(1)));
        self::assertFalse(Prime::isPrime(Number::of(9)));
        self::assertSame('11', Prime::nextPrime(Number::of(10))->value());
        self::assertSame('1', IntegerSquareRoot::of(Number::of(2))->value());

        $factorization = Factorization::of(Number::of(60));
        self::assertSame('2', $factorization->primeFactors()[0]->value());
        self::assertSame('2', $factorization->exponents()[0]->value());
        self::assertSame([], Factorization::of(Number::of(1))->primeFactors());
        self::assertSame([], Factorization::of(Number::of(1))->exponents());
    }

    public function testCongruenceModularArithmeticAndInverse(): void
    {
        self::assertTrue(Congruence::of(Number::of(14), Number::of(2), Number::of(12)));
        self::assertFalse(Congruence::of(Number::of(14), Number::of(3), Number::of(12)));
        self::assertTrue(Congruence::of(Number::of(-8), Number::of(6), Number::of(7)));
        self::assertTrue(Congruence::of(Number::of(10), Number::of(0), Number::of(1)));

        self::assertSame('4', ModularArithmetic::add(Number::of(7), Number::of(9), Number::of(12))->value());
        self::assertSame('10', ModularArithmetic::subtract(Number::of(7), Number::of(9), Number::of(12))->value());
        self::assertSame('3', ModularArithmetic::multiply(Number::of(7), Number::of(9), Number::of(12))->value());
        self::assertSame('5', ModularArithmetic::power(Number::of(3), Number::of(5), Number::of(7))->value());

        self::assertSame('5', ModularInverse::of(Number::of(3), Number::of(7))->value());
        self::assertSame('12', ModularInverse::of(Number::of(10), Number::of(17))->value());
    }

    public function testIntegerSquareRootAndEulerTotient(): void
    {
        self::assertSame('0', IntegerSquareRoot::of(Number::of(0))->value());
        self::assertSame('1', IntegerSquareRoot::of(Number::of(1))->value());
        self::assertSame('3', IntegerSquareRoot::of(Number::of(9))->value());
        self::assertSame('9', IntegerSquareRoot::of(Number::of(81))->value());
        self::assertSame('8', IntegerSquareRoot::of(Number::of(80))->value());
        self::assertSame('1', EulerTotient::of(Number::of(1))->value());
        self::assertSame('4', EulerTotient::of(Number::of(5))->value());
        self::assertSame('12', EulerTotient::of(Number::of(13))->value());
        self::assertSame('6', EulerTotient::of(Number::of(18))->value());
    }

    public function testDivisionByZeroIsRejected(): void
    {
        $this->expectException(DivisionByZeroError::class);
        Divisibility::isDivisibleBy(Number::of(5), Number::of(0));
    }
}
