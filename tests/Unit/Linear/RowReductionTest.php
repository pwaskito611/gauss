<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Linear;

use Gauss\Linear\Matrix;
use Gauss\Linear\RowReduction;
use PHPUnit\Framework\TestCase;

final class RowReductionTest extends TestCase
{
    public function testReducedEchelonFormRankAndPivots(): void
    {
        $matrix = Matrix::of([[1, 2], [2, 4]]);
        $reduction = RowReduction::of($matrix);
        $rref = $reduction->reducedEchelonForm();

        self::assertSame([['1', '2'], ['0', '0']], $this->valuesOf($rref));
        self::assertSame(1, $reduction->rank());
        self::assertSame([0], $reduction->pivotColumns());
        self::assertSame([1], $reduction->freeColumns());
        self::assertNotEmpty($reduction->operations());
        self::assertSame([['1', '2'], ['2', '4']], $this->valuesOf($matrix));
    }

    public function testRowSwapAndScalingAreRecorded(): void
    {
        $reduction = RowReduction::of(Matrix::of([[0, 1], [2, 0]]));
        $echelon = $reduction->echelonForm();

        self::assertSame([['1', '0'], ['0', '1']], $this->valuesOf($echelon));
        self::assertNotEmpty($reduction->operations());
    }

    public function testFreeColumnsWithMultiplePivots(): void
    {
        $reduction = RowReduction::of(Matrix::of([
            [1, 0, 2, 0],
            [0, 1, 3, 0],
        ]));

        self::assertSame([0, 1], $reduction->pivotColumns());
        self::assertSame([2, 3], $reduction->freeColumns());
    }

    public function testFreeColumnsWhenAllOrNoColumnsArePivots(): void
    {
        $fullRank = RowReduction::of(Matrix::identity(3));
        self::assertSame([], $fullRank->freeColumns());

        $zero = RowReduction::of(Matrix::zero(2, 3));
        self::assertSame([], $zero->pivotColumns());
        self::assertSame([0, 1, 2], $zero->freeColumns());
    }

    /** @return list<list<string>> */
    private function valuesOf(Matrix $matrix): array
    {
        $rows = [];
        for ($row = 0; $row < $matrix->rows(); $row++) {
            $rows[] = array_map(
                static fn ($value): string => $value->value(),
                $matrix->row($row)->values()
            );
        }
        return $rows;
    }
}
