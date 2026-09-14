<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Linear;

use Gauss\Linear\Eigen;
use Gauss\Linear\Matrix;
use LogicException;
use PHPUnit\Framework\TestCase;

final class EigenTest extends TestCase
{
    public function testDiagonalEigenvaluesVectorsAndCharacteristicPolynomial(): void
    {
        $matrix = Matrix::diagonal([2, 3]);
        $eigen = Eigen::of($matrix);

        self::assertSame(['2', '3'], array_map(static fn ($value): string => $value->value(), $eigen->values()->values()));
        self::assertSame(['1', '0'], array_map(static fn ($value): string => $value->value(), $eigen->vectors()[0]->values()));
        self::assertSame(['0', '1'], array_map(static fn ($value): string => $value->value(), $eigen->vectors()[1]->values()));
        self::assertSame('6', $eigen->characteristicPolynomial()->coefficient(0)->value());
        self::assertSame('-5', $eigen->characteristicPolynomial()->coefficient(1)->value());
        self::assertSame('1', $eigen->characteristicPolynomial()->coefficient(2)->value());
    }

    public function testEigenvectorEquationForSupportedSubset(): void
    {
        $matrix = Matrix::diagonal([2, 3]);
        $eigen = Eigen::of($matrix);

        foreach ($eigen->vectors() as $index => $vector) {
            self::assertSame(
                $matrix->multiplyVector($vector)->values()[$index]->value(),
                $eigen->values()->get($index)->mul($vector->get($index))->value()
            );
        }
    }

    public function testGeneralNonDiagonalCaseIsExplicitlyUnsupported(): void
    {
        $this->expectException(LogicException::class);
        Eigen::of(Matrix::of([[1, 1], [0, 2]]));
    }
}
