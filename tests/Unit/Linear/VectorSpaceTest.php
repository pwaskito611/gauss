<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Linear;

use Gauss\Linear\Vector;
use Gauss\Linear\VectorSpace;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class VectorSpaceTest extends TestCase
{
    public function testBasisDimensionAndMembership(): void
    {
        $space = VectorSpace::of(Vector::of(1, 0), Vector::of(0, 1));

        self::assertSame(2, $space->dimension());
        self::assertTrue($space->isBasis());
        self::assertTrue($space->contains(Vector::of(3, 4)));
        self::assertSame(2, $space->rank());
    }

    public function testDependentGeneratorsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        VectorSpace::of(Vector::of(1, 2), Vector::of(2, 4));
    }

    public function testProperSubspaceAndZeroSpace(): void
    {
        $space = VectorSpace::of(Vector::of(1, 2));

        self::assertTrue($space->contains(Vector::of(3, 6)));
        self::assertFalse($space->contains(Vector::of(1, 0)));
        self::assertTrue(VectorSpace::zero(2)->contains(Vector::zero(2)));
        self::assertFalse(VectorSpace::zero(2)->contains(Vector::of(1, 0)));
    }
}
