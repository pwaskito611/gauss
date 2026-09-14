<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Algebra;

use Gauss\Algebra\Monomial;
use Gauss\Number\Number;
use Gauss\Number\Rational;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class MonomialTest extends TestCase
{
    public function testEvaluateConstantAtZero(): void
    {
        $term = new Monomial(Number::of(7));

        self::assertSame('7', $term->evaluate(Number::of(0))->value());
    }

    public function testEvaluatePositiveDegreeAtZero(): void
    {
        $term = new Monomial(Number::of(3), 2);

        self::assertSame('0', $term->evaluate(Number::of(0))->value());
    }

    public function testZeroCoefficientCanBeIntegrated(): void
    {
        $integral = (new Monomial(Number::of(0), 2))->integral();

        self::assertTrue($integral->isZero());
        self::assertSame(3, $integral->degree());
    }

    public function testOperationsAndCalculus(): void
    {
        $term = new Monomial(Number::of('1/3'), 3);

        self::assertSame('1', $term->add(new Monomial(Number::of('2/3'), 3))->coefficient()->value());
        self::assertSame('2/3', $term->mul(new Monomial(Number::of(2), 1))->coefficient()->value());
        self::assertSame('1', $term->derivative()->coefficient()->value());
        self::assertSame('1/12', $term->integral()->coefficient()->value());
        self::assertSame(Rational::class, $term->coefficient()->type());
    }

    public function testDifferentDegreesCannotBeAdded(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Monomial(Number::of(1), 1))->add(new Monomial(Number::of(1), 2));
    }

    public function testNegativeDegreeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Monomial(Number::of(1), -1);
    }

    public function testTransformationsDoNotMutateTheOriginal(): void
    {
        $term = new Monomial(Number::of(3), 2);

        $term->derivative();
        $term->integral();

        self::assertSame('3', $term->coefficient()->value());
        self::assertSame(2, $term->degree());
    }
}
