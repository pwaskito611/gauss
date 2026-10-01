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

    public function testKernelAndImageHandleSingleRowSingleColumnAndZeroMaps(): void
    {
        $singleRow = LinearMap::fromMatrix(Matrix::of([[1, 2, 3]]));
        self::assertSame(2, $singleRow->kernel()->dimension());
        self::assertSame(1, $singleRow->image()->dimension());
        self::assertTrue($singleRow->kernel()->contains(Vector::of(-2, 1, 0)));

        $singleColumn = LinearMap::fromMatrix(Matrix::of([[1], [2]]));
        self::assertSame(0, $singleColumn->kernel()->dimension());
        self::assertSame(1, $singleColumn->image()->dimension());

        $zeroRowMap = LinearMap::fromMatrix(Matrix::zero(1, 3));
        self::assertSame(3, $zeroRowMap->kernel()->dimension());
        self::assertSame(0, $zeroRowMap->image()->dimension());
        self::assertTrue($zeroRowMap->kernel()->contains(Vector::of(1, 0, 0)));

        $zeroColumnMap = LinearMap::fromMatrix(Matrix::zero(3, 1));
        self::assertSame(1, $zeroColumnMap->kernel()->dimension());
        self::assertSame(0, $zeroColumnMap->image()->dimension());
    }

    /** @return list<string> */
    private function valuesOf(Vector $vector): array
    {
        return array_map(static fn ($value): string => $value->value(), $vector->values());
    }
}
