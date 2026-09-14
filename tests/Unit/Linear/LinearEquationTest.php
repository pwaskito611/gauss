<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Linear;

use Gauss\Linear\LinearEquation;
use Gauss\Linear\Vector;
use Gauss\Number\Number;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LinearEquationTest extends TestCase
{
    public function testCreateEvaluateAndInspect(): void
    {
        $equation = LinearEquation::of(Vector::of(2, 3), 5);

        self::assertSame(2, $equation->variables());
        self::assertSame('5', $equation->constant()->value());
        self::assertSame('5', $equation->evaluate(Vector::of(1, 1))->value());
        self::assertSame(['2', '3', '5'], $this->valuesOf($equation->toRow()));
    }

    public function testScaleAndNormalizeAreImmutable(): void
    {
        $equation = LinearEquation::of(Vector::of(2, 4), 6);

        self::assertSame(['4', '8'], $this->valuesOf($equation->scale(Number::of(2))->coefficients()));
        self::assertSame(['1', '2'], $this->valuesOf($equation->normalize()->coefficients()));
        self::assertSame(['2', '4'], $this->valuesOf($equation->coefficients()));
    }

    public function testEvaluateDimensionMismatchIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        LinearEquation::of(Vector::of(1, 2), 3)->evaluate(Vector::of(1));
    }

    /** @return list<string> */
    private function valuesOf($value): array
    {
        if ($value instanceof Vector) {
            return array_map(static fn ($item): string => $item->value(), $value->values());
        }
        return array_map(static fn ($item): string => $item->value(), $value->row(0)->values());
    }
}
