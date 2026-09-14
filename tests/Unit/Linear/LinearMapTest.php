<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Linear;

use Gauss\Linear\LinearMap;
use Gauss\Linear\Matrix;
use Gauss\Linear\Vector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LinearMapTest extends TestCase
{
    public function testApplyAndDimensions(): void
    {
        $map = LinearMap::fromMatrix(Matrix::of([[1, 2], [3, 4]]));

        self::assertSame(['5', '11'], $this->valuesOf($map->apply(Vector::of(1, 2))));
        self::assertSame(2, $map->domainDimension());
        self::assertSame(2, $map->codomainDimension());
        self::assertTrue($map->isBijective());
    }

    public function testApplyDimensionMismatch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        LinearMap::fromMatrix(Matrix::identity(2))->apply(Vector::of(1));
    }

    public function testRankNullityAndKernel(): void
    {
        $map = LinearMap::fromMatrix(Matrix::of([[1, 2], [2, 4]]));
        $kernel = $map->kernel();

        self::assertSame(1, $map->rank());
        self::assertSame(1, $map->nullity());
        self::assertSame(2, $map->rank() + $map->nullity());
        self::assertTrue($kernel->contains(Vector::of(-2, 1)));
        self::assertFalse($map->isInjective());
        self::assertFalse($map->isSurjective());
    }

    public function testImageAndSurjectivity(): void
    {
        $map = LinearMap::fromMatrix(Matrix::of([[1, 0, 0], [0, 1, 0]]));

        self::assertSame(2, $map->rank());
        self::assertTrue($map->isSurjective());
        self::assertFalse($map->isInjective());
        self::assertTrue($map->image()->contains(Vector::of(4, 5)));
    }

    /** @return list<string> */
    private function valuesOf(Vector $vector): array
    {
        return array_map(static fn ($value): string => $value->value(), $vector->values());
    }
}
