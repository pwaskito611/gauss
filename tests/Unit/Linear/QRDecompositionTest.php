<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Linear;

use Gauss\Linear\Matrix;
use Gauss\Linear\QRDecomposition;
use Gauss\Linear\Vector;
use InvalidArgumentException;
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

    public function testSquareSolveReconstructsRightHandSide(): void
    {
        $matrix = Matrix::of([[2, 1], [1, 2]]);
        $rhs = Vector::of(5, 4);
        $solution = QRDecomposition::of($matrix)->solve($rhs);
        $reconstructed = $matrix->multiplyVector($solution);

        for ($index = 0; $index < $rhs->dimension(); $index++) {
            $this->assertApproximately($rhs->get($index)->value(), $reconstructed->get($index)->value());
        }
    }

    public function testTallSolveReturnsLeastSquaresSolution(): void
    {
        $matrix = Matrix::of([[1, 0], [0, 1], [1, 1]]);
        $rhs = Vector::of(1, 2, 0);
        $solution = QRDecomposition::of($matrix)->solve($rhs);
        $residual = $matrix->multiplyVector($solution)->sub($rhs);

        $normalResidual = $matrix->transpose()->multiplyVector($residual);
        for ($index = 0; $index < $normalResidual->dimension(); $index++) {
            $this->assertApproximately('0', $normalResidual->get($index)->value());
        }
    }

    public function testUnderdeterminedMatricesRemainUnsupported(): void
    {
        $this->expectException(InvalidArgumentException::class);

        QRDecomposition::of(Matrix::of([[1, 0, 0], [0, 1, 0]]));
    }

    public function testLinearlyDependentColumnsRemainRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        QRDecomposition::of(Matrix::of([[1, 2], [0, 0], [0, 0]]));
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
