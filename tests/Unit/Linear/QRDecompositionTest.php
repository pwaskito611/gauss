<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Linear;

use Gauss\Linear\Matrix;
use Gauss\Linear\QRDecomposition;
use Gauss\Linear\Vector;
use PHPUnit\Framework\TestCase;

final class QRDecompositionTest extends TestCase
{
    public function testDecompositionInvariants(): void
    {
        $matrix = Matrix::of([[1.0, 0.0], [0.0, 1.0], [1.0, 1.0]]);
        $qr = QRDecomposition::of($matrix);

        $this->assertMatrixApproximately(Matrix::identity(2), $qr->Q()->transpose()->multiply($qr->Q()));
        $this->assertMatrixApproximately($matrix, $qr->Q()->multiply($qr->R()));
    }

    public function testSolve(): void
    {
        $matrix = Matrix::of([[1.0, 0.0], [0.0, 1.0], [1.0, 1.0]]);
        $solution = QRDecomposition::of($matrix)->solve(Vector::of(1.0, 2.0, 3.0));

        $this->assertApproximately('1', $solution->get(0)->value());
        $this->assertApproximately('2', $solution->get(1)->value());
    }

    private function assertMatrixApproximately(Matrix $expected, Matrix $actual): void
    {
        self::assertSame($expected->shape(), $actual->shape());
        for ($row = 0; $row < $expected->rows(); $row++) {
            for ($column = 0; $column < $expected->columns(); $column++) {
                $this->assertApproximately(
                    $expected->get($row, $column)->value(),
                    $actual->get($row, $column)->value()
                );
            }
        }
    }

    private function assertApproximately(string $expected, string $actual): void
    {
        $difference = bcsub($expected, $actual, 50);
        if (str_starts_with($difference, '-')) {
            $difference = substr($difference, 1);
        }
        self::assertLessThanOrEqual('0.000000000001', $difference);
    }
}
