<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Linear;

use Gauss\Linear\Vector;
use Gauss\Number\Number;
use Gauss\Number\Rational;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

final class VectorTest extends TestCase
{
    public function testCreateAndInspectVector(): void
    {
        $vector = Vector::of(1, 2, 3);

        self::assertSame(3, $vector->dimension());
        self::assertSame('2', $vector->get(1)->value());
        self::assertCount(3, $vector->values());
    }

    public function testEmptyVectorIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Vector::of();
    }

    public function testValuesCannotMutateInternalState(): void
    {
        $vector = Vector::of(1, 2);
        $values = $vector->values();
        $values[0] = Number::of(99);

        self::assertSame('1', $vector->get(0)->value());
    }

    public function testZeroAndBasisFactories(): void
    {
        self::assertTrue(Vector::zero(3)->isZero());
        self::assertSame(['0', '1', '0'], array_map(
            static fn ($value): string => $value->value(),
            Vector::basis(3, 1)->values()
        ));
    }

    public function testArithmetic(): void
    {
        $left = Vector::of(1, 2, 3);
        $right = Vector::of(3, 2, 1);

        self::assertSame(['4', '4', '4'], $this->valuesOf($left->add($right)));
        self::assertSame(['-2', '0', '2'], $this->valuesOf($left->sub($right)));
        self::assertSame(['2', '4', '6'], $this->valuesOf($left->scale(Number::of(2))));
        self::assertSame(['-1', '-2', '-3'], $this->valuesOf($left->negate()));
        self::assertSame(['1', '2', '3'], $this->valuesOf($left));
    }

    public function testDimensionMismatchIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Vector::of(1, 2)->add(Vector::of(1));
    }

    public function testInvalidIndexIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Vector::of(1, 2)->get(2);
    }

    public function testDotProductAndNormInvariant(): void
    {
        $vector = Vector::of(3, 4);

        self::assertSame('25', $vector->dot($vector)->value());
        self::assertSame('25', $vector->normSquared()->value());
        self::assertSame('5.00000000000000000000000000000000000000000000000000', $vector->norm()->value());
    }

    public function testCrossProduct(): void
    {
        $result = Vector::of(1, 0, 0)->cross(Vector::of(0, 1, 0));

        self::assertSame(['0', '0', '1'], $this->valuesOf($result));
    }

    public function testCrossProductRequiresThreeDimensions(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Vector::of(1, 2)->cross(Vector::of(2, 1));
    }

    public function testDistanceAndNormalization(): void
    {
        $vector = Vector::of(3, 4);

        self::assertSame('0', $vector->distance($vector)->value());
        self::assertSame(
            ['0.60000000000000000000000000000000000000000000000000', '0.80000000000000000000000000000000000000000000000000'],
            $this->valuesOf($vector->normalize())
        );
    }

    public function testZeroVectorCannotBeNormalized(): void
    {
        $this->expectException(LogicException::class);

        Vector::zero(2)->normalize();
    }

    public function testZeroOrthogonalityAndParallelism(): void
    {
        self::assertTrue(Vector::of(1, 0)->isOrthogonalTo(Vector::of(0, 2)));
        self::assertTrue(Vector::of(1, 2)->isParallelTo(Vector::of(2, 4)));
        self::assertTrue(Vector::zero(2)->isZero());
    }

    public function testMapReturnsAValueVector(): void
    {
        $mapped = Vector::of(1, 2)->map(
            static fn ($value): Number => Number::of($value->mul(Number::of(2)))
        );

        self::assertSame(['2', '4'], $this->valuesOf($mapped));
        self::assertSame(Rational::class, $mapped->get(0)->type());
    }

    public function testNormalizeRejectsUnsupportedNonRealNorm(): void
    {
        $this->expectException(LogicException::class);

        Vector::of(Number::of('1/2'), Number::of('1/2'))->norm();
    }

    /** @return list<string> */
    private function valuesOf(Vector $vector): array
    {
        return array_map(
            static fn ($value): string => $value->value(),
            $vector->values()
        );
    }
}
