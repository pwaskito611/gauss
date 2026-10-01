<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Linear;

use DivisionByZeroError;
use Gauss\Linear\Matrix;
use Gauss\Linear\Vector;
use Gauss\Number\Number;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

final class MatrixTest extends TestCase
{
    public function testConstructionAndInspection(): void
    {
        $matrix = Matrix::of([[1, 2], [3, 4]]);

        self::assertSame([2, 2], $matrix->shape());
        self::assertSame('2', $matrix->get(0, 1)->value());
        self::assertSame(['1', '2'], $this->valuesOf($matrix->row(0)));
        self::assertSame(['2', '4'], $this->valuesOf($matrix->column(1)));
        self::assertSame(['1', '4'], $this->valuesOf($matrix->diagonalValues()));
    }

    public function testFactoriesAndPredicates(): void
    {
        $identity = Matrix::identity(2);
        $diagonal = Matrix::diagonal([2, 3]);

        self::assertTrue($identity->isIdentity());
        self::assertTrue($diagonal->isDiagonal());
        self::assertTrue($diagonal->isSymmetric());
        self::assertTrue(Matrix::zero(2, 2)->isSingular());
    }

    public function testRaggedAndEmptyMatricesAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Matrix::of([[1, 2], [3]]);
    }

    public function testArithmeticAndImmutability(): void
    {
        $matrix = Matrix::of([[1, 2], [3, 4]]);
        $other = Matrix::of([[4, 3], [2, 1]]);

        self::assertSame([['5', '5'], ['5', '5']], $this->valuesOfMatrix($matrix->add($other)));
        self::assertSame([['-3', '-1'], ['1', '3']], $this->valuesOfMatrix($matrix->sub($other)));
        self::assertSame([['2', '4'], ['6', '8']], $this->valuesOfMatrix($matrix->scale(Number::of(2))));
        self::assertSame([['-1', '-2'], ['-3', '-4']], $this->valuesOfMatrix($matrix->negate()));
        self::assertSame([['1', '2'], ['3', '4']], $this->valuesOfMatrix($matrix));
    }

    public function testTransposeAndMultiplicationInvariants(): void
    {
        $matrix = Matrix::of([[1, 2], [3, 4]]);

        self::assertSame([['1', '3'], ['2', '4']], $this->valuesOfMatrix($matrix->transpose()));
        self::assertTrue($matrix->transpose()->transpose()->equals($matrix));
        self::assertTrue($matrix->multiply(Matrix::identity(2))->equals($matrix));
        self::assertTrue(Matrix::identity(2)->multiply($matrix)->equals($matrix));
        self::assertSame(['5', '11'], $this->valuesOf($matrix->multiplyVector(Vector::of(1, 2))));
    }

    public function testDimensionMismatchIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Matrix::of([[1, 2]])->multiply(Matrix::of([[1, 2]]));
    }

    public function testMatrixVectorDimensionMismatchIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Matrix::identity(2)->multiplyVector(Vector::of(1));
    }

    public function testDeterminantMinorCofactorAdjugateAndInverse(): void
    {
        $matrix = Matrix::of([[1, 2], [3, 4]]);

        self::assertSame('-2', $matrix->determinant()->value());
        self::assertSame('4', $matrix->minor(0, 0)->get(0, 0)->value());
        self::assertSame('4', $matrix->cofactor(0, 0)->value());
        self::assertSame([['4', '-2'], ['-3', '1']], $this->valuesOfMatrix($matrix->adjugate()));
        self::assertTrue($matrix->multiply($matrix->inverse())->equals(Matrix::identity(2)));
    }

    public function testOneByOneDeterminantAndAdjugateRemainDefined(): void
    {
        $matrix = Matrix::of([[7]]);

        self::assertSame('7', $matrix->determinant()->value());
        self::assertTrue($matrix->adjugate()->equals(Matrix::identity(1)));
    }

    public function testOneByOneMinorRemainsUnsupported(): void
    {
        $this->expectException(LogicException::class);

        Matrix::of([[7]])->minor(0, 0);
    }

    public function testOneByOneCofactorIsOneAndInverseRemainsCorrect(): void
    {
        $matrix = Matrix::of([[7]]);

        self::assertSame('1', $matrix->cofactor(0, 0)->value());
        self::assertSame(
            Number::of(1)->div(7)->value(),
            $matrix->inverse()->get(0, 0)->value()
        );
    }

    public function testMinorRemovesTheRequestedRowAndColumnInOrder(): void
    {
        $minor = Matrix::of([[1, 2, 3], [4, 5, 6]])->minor(0, 1);

        self::assertSame([1, 2], $minor->shape());
        self::assertSame([['4', '6']], $this->valuesOfMatrix($minor));
    }

    public function testTraceAndRank(): void
    {
        $matrix = Matrix::of([[1, 2], [2, 4]]);

        self::assertSame('5', $matrix->trace()->value());
        self::assertSame(1, $matrix->rank());
        self::assertTrue($matrix->isSingular());
    }

    public function testInvalidSquareOperationsAreRejected(): void
    {
        $matrix = Matrix::of([[1, 2, 3], [4, 5, 6]]);

        $this->expectException(LogicException::class);
        $matrix->determinant();
    }

    public function testSingularInverseIsRejected(): void
    {
        $this->expectException(DivisionByZeroError::class);
        Matrix::of([[1, 2], [2, 4]])->inverse();
    }

    public function testZeroChecksUseNumericComparison(): void
    {
        $matrix = Matrix::of([[1, 0], [0, Number::of('0.000')]]);

        self::assertSame('0', $matrix->determinant()->value());
        $this->expectException(DivisionByZeroError::class);
        $matrix->inverse();
    }

    /** @return list<string> */
    private function valuesOf(Vector $vector): array
    {
        return array_map(static fn ($value): string => $value->value(), $vector->values());
    }

    /** @return list<list<string>> */
    private function valuesOfMatrix(Matrix $matrix): array
    {
        $rows = [];
        for ($row = 0; $row < $matrix->rows(); $row++) {
            $rows[] = $this->valuesOf($matrix->row($row));
        }
        return $rows;
    }
}
