<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Linear;

use Gauss\Linear\LinearSystem;
use Gauss\Linear\Matrix;
use Gauss\Linear\Solution\InfiniteSolutions;
use Gauss\Linear\Solution\NoSolution;
use Gauss\Linear\Solution\UniqueSolution;
use Gauss\Linear\Vector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LinearSystemTest extends TestCase
{
    public function testUniqueSolution(): void
    {
        $system = LinearSystem::of(Matrix::of([[1, 1], [1, -1]]), Vector::of(3, 1));
        $solution = $system->solve();

        self::assertInstanceOf(UniqueSolution::class, $solution);
        self::assertSame(['2', '1'], $this->valuesOf($solution->vector()));
        self::assertTrue($system->hasSolution());
        self::assertTrue($system->isConsistent());
    }

    public function testNoSolution(): void
    {
        $system = LinearSystem::of(Matrix::of([[1, 1], [1, 1]]), Vector::of(2, 3));

        self::assertInstanceOf(NoSolution::class, $system->solve());
        self::assertFalse($system->hasSolution());
    }

    public function testInfiniteSolutions(): void
    {
        $system = LinearSystem::of(Matrix::of([[1, 1], [2, 2]]), Vector::of(2, 4));
        $solution = $system->solve();

        self::assertInstanceOf(InfiniteSolutions::class, $solution);
        self::assertSame([1], $solution->freeColumns());
    }

    public function testMatrixAndRhsDimensionsMustMatch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        LinearSystem::of(Matrix::identity(2), Vector::of(1));
    }

    /** @return list<string> */
    private function valuesOf(\Gauss\Linear\Vector $vector): array
    {
        return array_map(static fn ($value): string => $value->value(), $vector->values());
    }
}
