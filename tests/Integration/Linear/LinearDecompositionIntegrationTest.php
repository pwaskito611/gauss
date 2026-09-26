<?php

declare(strict_types=1);

namespace Gauss\Tests\Integration\Linear;

use Gauss\Linear\Matrix;
use Gauss\Linear\LUDecomposition;
use Gauss\Linear\QRDecomposition;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LinearDecompositionIntegrationTest extends TestCase
{
    public function testItReconstructsAMatrixFromItsQrDecomposition(): void
    {
        $matrix = Matrix::of([[1, 1], [1, 2], [1, 3]]);
        $decomposition = QRDecomposition::of($matrix);
        $reconstructed = $decomposition->Q()->multiply($decomposition->R());

        self::assertSame($matrix->shape(), $reconstructed->shape());
        for ($row = 0; $row < $matrix->rows(); $row++) {
            for ($column = 0; $column < $matrix->columns(); $column++) {
                self::assertLessThanOrEqual(
                    0,
                    $reconstructed->get($row, $column)
                        ->sub($matrix->get($row, $column))
                        ->abs()
                        ->compare('0.000000000001'),
                );
            }
        }
    }

    public function testItReconstructsThePermutedMatrixFromItsLuDecomposition(): void
    {
        $matrix = Matrix::of([[0, 2], [1, 3]]);
        $decomposition = LUDecomposition::of($matrix);
        $permuted = $decomposition->P()->multiply($matrix);
        $reconstructed = $decomposition->L()->multiply($decomposition->U());

        for ($row = 0; $row < $matrix->rows(); $row++) {
            for ($column = 0; $column < $matrix->columns(); $column++) {
                self::assertSame(
                    0,
                    $permuted->get($row, $column)->compare($reconstructed->get($row, $column)),
                );
            }
        }
    }

    public function testItRejectsIncompatibleMatrixAndVectorAtTheBoundary(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Matrix::of([[1, 2], [3, 4]])->multiplyVector(\Gauss\Linear\Vector::of(1));
    }
}