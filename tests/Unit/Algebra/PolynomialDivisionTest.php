<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Algebra;

use Gauss\Algebra\Polynomial;
use Gauss\Number\Number;
use DivisionByZeroError;
use PHPUnit\Framework\TestCase;

final class PolynomialDivisionTest extends TestCase
{
    public function testDivisionReturnsQuotientAndRemainder(): void
    {
        $dividend = Polynomial::of([0 => Number::of(-1), 1 => Number::of(0), 2 => Number::of(1)]);
        $divisor = Polynomial::of([0 => Number::of(-1), 1 => Number::of(1)]);

        $division = $dividend->divide($divisor);

        self::assertSame('1', $division->quotient()->coefficient(1)->value());
        self::assertTrue($division->remainder()->isZero());
    }

    public function testDivisionKeepsRemainderBelowDivisorDegree(): void
    {
        $dividend = Polynomial::of([0 => Number::of(1), 2 => Number::of(1)]);
        $divisor = Polynomial::of([0 => Number::of(1), 1 => Number::of(1)]);

        $division = $dividend->divide($divisor);

        self::assertSame('1', $division->quotient()->coefficient(1)->value());
        self::assertSame('-1', $division->quotient()->constantTerm()->value());
        self::assertSame('2', $division->remainder()->constantTerm()->value());
        self::assertSame(0, $division->remainder()->degree());

        $reconstructed = $division->quotient()->mul($divisor)
            ->add($division->remainder());
        self::assertSame([0, 2], array_keys($reconstructed->coefficients()));
        self::assertSame('1', $reconstructed->coefficient(0)->value());
        self::assertSame('1', $reconstructed->coefficient(2)->value());
    }

    public function testDivisionByConstantAndNegativeCoefficients(): void
    {
        $constantDivision = Polynomial::of([
            0 => Number::of(6),
            1 => Number::of(4),
            2 => Number::of(2),
        ])->divide(Polynomial::constant(Number::of(2)));

        self::assertSame('3', $constantDivision->quotient()->coefficient(0)->value());
        self::assertSame('2', $constantDivision->quotient()->coefficient(1)->value());
        self::assertSame('1', $constantDivision->quotient()->coefficient(2)->value());
        self::assertTrue($constantDivision->remainder()->isZero());

        $negativeDivision = Polynomial::of([
            0 => Number::of(1),
            2 => Number::of(-1),
        ])->divide(Polynomial::of([
            0 => Number::of(-1),
            1 => Number::of(1),
        ]));

        self::assertSame('-1', $negativeDivision->quotient()->coefficient(0)->value());
        self::assertSame('-1', $negativeDivision->quotient()->coefficient(1)->value());
        self::assertTrue($negativeDivision->remainder()->isZero());
    }

    public function testDivisionByZeroPolynomialThrows(): void
    {
        $this->expectException(DivisionByZeroError::class);

        Polynomial::of([0 => Number::of(1)])->divide(Polynomial::zero(Number::of(0)));
    }

    public function testZeroDividendProducesZeroQuotientAndRemainder(): void
    {
        $division = Polynomial::zero(Number::of(0))->divide(
            Polynomial::of([0 => Number::of(1), 1 => Number::of(1)])
        );

        self::assertTrue($division->quotient()->isZero());
        self::assertTrue($division->remainder()->isZero());
    }

    public function testDivisionPreservesTheDividendIdentity(): void
    {
        $dividend = Polynomial::of([0 => Number::of(-1), 2 => Number::of(1)]);
        $divisor = Polynomial::of([0 => Number::of(-1), 1 => Number::of(1)]);
        $division = $dividend->divide($divisor);

        $reconstructed = $division->quotient()->mul($divisor)->add($division->remainder());

        self::assertSame($dividend->evaluate(Number::of(2))->value(), $reconstructed->evaluate(Number::of(2))->value());
        self::assertSame($dividend->evaluate(Number::of(3))->value(), $reconstructed->evaluate(Number::of(3))->value());
        self::assertTrue($division->remainder()->isZero());
    }

    public function testLowerDegreeDividendProducesZeroQuotient(): void
    {
        $dividend = Polynomial::of([0 => Number::of(1), 1 => Number::of(1)]);
        $divisor = Polynomial::of([0 => Number::of(1), 1 => Number::of(1), 2 => Number::of(1)]);
        $division = $dividend->divide($divisor);

        self::assertTrue($division->quotient()->isZero());
        self::assertSame('1', $division->remainder()->constantTerm()->value());
        self::assertSame('1', $division->remainder()->coefficient(1)->value());
    }

    public function testDivisionCanReconstructTheDividend(): void
    {
        $dividend = Polynomial::of([
            0 => Number::of(1),
            1 => Number::of(0),
            2 => Number::of(1),
        ]);
        $divisor = Polynomial::of([0 => Number::of(1), 1 => Number::of(1)]);
        $division = $dividend->divide($divisor);

        $reconstructed = $division
            ->quotient()
            ->mul($divisor)
            ->add($division->remainder());

        self::assertSame($dividend->evaluate(Number::of(2))->value(), $reconstructed->evaluate(Number::of(2))->value());
        self::assertSame($dividend->evaluate(Number::of(3))->value(), $reconstructed->evaluate(Number::of(3))->value());
    }

    public function testDivisionFailsFastWhenLeadingTermDoesNotReduceRemainder(): void
    {
        $this->expectException(\RuntimeException::class);
        Polynomial::of([1 => Number::of(1)])
            ->divide(Polynomial::of([1 => Number::of(3)]));
    }

    public function testZeroDividendDivisionTerminatesAndReturnsCanonicalZero(): void
    {
        $division = Polynomial::zero(Number::of(0))->divide(
            Polynomial::of([0 => Number::of(2), 1 => Number::of(1)])
        );

        self::assertTrue($division->quotient()->isZero());
        self::assertTrue($division->remainder()->isZero());
    }

    public function testDecimalDivisionPreservesNumberPrecision(): void
    {
        $dividend = Polynomial::constant(Number::of('0.2'));
        $divisor = Polynomial::constant(Number::of('0.2'));

        $division = $dividend->divide($divisor);

        self::assertSame(
            '1',
            $division->quotient()->constantTerm()->value()
        );
        self::assertTrue($division->remainder()->isZero());
    }
}
