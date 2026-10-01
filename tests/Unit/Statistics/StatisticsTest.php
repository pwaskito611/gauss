<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Statistics;

use DivisionByZeroError;
use Gauss\Linear\Matrix;
use Gauss\Number\Number;
use Gauss\Statistics\Statistics;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class StatisticsTest extends TestCase
{
    public function testCoreDescriptiveStatisticsReturnNumbers(): void
    {
        $data = [1, 2, 2, 4, 6];

        self::assertSame(5, Statistics::count($data));
        self::assertNumberValue('15', Statistics::sum($data));
        self::assertNumberValue('3', Statistics::mean($data));
        self::assertNumberValue('2', Statistics::median($data));
        self::assertNumberValue('2', Statistics::mode($data));
        self::assertNumberValue('1', Statistics::min($data));
        self::assertNumberValue('6', Statistics::max($data));
        self::assertNumberValue('5', Statistics::range($data));
    }

    public function testMedianSupportsEvenObservationCount(): void
    {
        self::assertNumberValue('5', Statistics::median([1, 2, 8, 9]));
    }

    public function testPrimitiveInputsAreNormalizedAtTheBoundary(): void
    {
        $result = Statistics::mean(['0.5', '1.5']);

        self::assertInstanceOf(Number::class, $result);
        self::assertSame('1', $result->value());
    }

    public function testNumberCanonicalRepresentationMatchesNumericEquality(): void
    {
        $oneForms = ['1', '01', '+1', '1.0', '1.00', '1e0', '1.0e0'];
        $one = Number::of($oneForms[0]);

        foreach ($oneForms as $form) {
            $value = Number::of($form);
            self::assertSame(0, $one->compare($value));
            self::assertSame($one->value(), $value->value());
        }

        foreach (['0', '-0', '0.000', '-0.000', '+0', '0e1'] as $form) {
            $value = Number::of($form);
            self::assertSame('0', $value->value());
            self::assertSame(0, Number::of(0)->compare($value));
        }

        foreach (['0.5', '.5', '5e-1'] as $form) {
            $value = Number::of($form);
            self::assertSame('0.5', $value->value());
            self::assertSame(0, Number::of('0.5')->compare($value));
        }
    }

    public function testEmptyDataIsRejectedExceptForCount(): void
    {
        self::assertSame(0, Statistics::count([]));

        $this->expectException(InvalidArgumentException::class);
        Statistics::sum([]);
    }

    public function testDispersionStatisticsUsePopulationAndSampleFormulas(): void
    {
        $data = [1, 2, 3, 4];

        self::assertNumberValue('1.25', Statistics::populationVariance($data));
        self::assertNumberValue('1.666666666666666666666666666666666666666666666666666666666667', Statistics::sampleVariance($data));
        self::assertNumberValue('1', Statistics::meanAbsoluteDeviation($data));
        self::assertNumberValue('1', Statistics::medianAbsoluteDeviation($data));
        self::assertNumberValue('1.5', Statistics::interquartileRange($data));
        self::assertNumberApproximately('0.44721359549995793928183473374625524708812367192231', Statistics::coefficientOfVariation($data));
        self::assertNumberApproximately('1.11803398874989484820458683436563811772030917980577', Statistics::populationStandardDeviation($data));
        self::assertNumberApproximately('1.29099444873580562839308846659413320361097390176387', Statistics::sampleStandardDeviation($data));
    }

    public function testQuantilesUseLinearInterpolation(): void
    {
        $data = [1, 2, 3, 4];

        self::assertNumberValue('1.75', Statistics::quantile($data, '0.25'));
        self::assertNumberValue('3.25', Statistics::percentile($data, 75));
        self::assertNumberValue('1.75', Statistics::quartile($data, 1));
        self::assertNumberValue('2.5', Statistics::decile($data, 5));
    }

    public function testQuantileBoundariesAndSmallDatasets(): void
    {
        $data = [1, 2, 3, 4];

        self::assertNumberValue('1', Statistics::quantile($data, 0));
        self::assertNumberValue('1.75', Statistics::quantile($data, '0.25'));
        self::assertNumberValue('2.5', Statistics::quantile($data, '0.5'));
        self::assertNumberValue('3.25', Statistics::quantile($data, '0.75'));
        self::assertNumberValue('4', Statistics::quantile($data, 1));
        self::assertNumberValue('7', Statistics::quantile([7], 0));
        self::assertNumberValue('7', Statistics::quantile([7], 1));
        self::assertNumberValue('1.5', Statistics::quantile([1, 3], '0.25'));
        self::assertNumberValue('2', Statistics::quantile([1, 1, 3, 3], '0.5'));
    }

    public function testQuantileLargeDatasetUsesExactPosition(): void
    {
        $data = range(0, 999);

        self::assertNumberValue('9.99', Statistics::quantile($data, '0.01'));
        self::assertNumberValue('499.5', Statistics::quantile($data, '0.5'));
        self::assertNumberValue('989.01', Statistics::quantile($data, '0.99'));
    }

    public function testQuantileAcceptsScientificNotationAndStaysInRange(): void
    {
        $data = [Number::of('1e2'), Number::of('2e2')];
        $quantile = Statistics::quantile($data, Number::of('5e-1'));

        self::assertNumberValue('150', $quantile);
        self::assertGreaterThanOrEqual(0, $quantile->compare(Number::of('1e2')));
        self::assertLessThanOrEqual(0, $quantile->compare(Number::of('2e2')));
    }

    public function testQuantilesRejectInvalidProbabilityAndLabels(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Statistics::quantile([1, 2], '1.5');
    }

    public function testMomentsAndShapeStatistics(): void
    {
        $data = [1, 2, 3];

        self::assertNumberValue('4.666666666666666666666666666666666666666666666666666666666667', Statistics::moment($data, 2));
        self::assertNumberValue('0.666666666666666666666666666666666666666666666666666666666667', Statistics::centralMoment($data, 2));
        self::assertSame(0, Statistics::skewness($data)->compare(0));
        self::assertNumberApproximately('1.5', Statistics::kurtosis($data));
        self::assertNumberApproximately('-1.5', Statistics::excessKurtosis($data));
    }

    public function testExcessKurtosisSubtractsThreeFromPearsonKurtosis(): void
    {
        $normalLike = [-1, 0, 0, 0, 0, 1];

        self::assertNumberApproximately('3', Statistics::kurtosis($normalLike));
        self::assertNumberApproximately('0', Statistics::excessKurtosis($normalLike));
    }

    public function testModeGroupsValuesByNumericEquality(): void
    {
        $mode = Statistics::mode([
            Number::of('1.0'),
            Number::of(2),
            Number::of('1.00'),
            Number::of(2),
            Number::of('1'),
        ]);

        self::assertSame(0, $mode->compare(1));
    }

    public function testModePreservesFirstMaximumTieAndCanonicalValue(): void
    {
        self::assertSame('7', Statistics::mode([7])->value());
        self::assertSame('2', Statistics::mode([2, 2, 2, 1])->value());
        self::assertSame('3', Statistics::mode([3, 1, 2])->value());
        self::assertSame('1', Statistics::mode([2, 1, 1, 2])->value());
        self::assertSame('1', Statistics::mode([
            Number::of('1.00'),
            Number::of(1),
            Number::of('2.0'),
            Number::of(2),
        ])->value());
    }

    public function testDependenceStatisticsAndSampleCovariance(): void
    {
        $left = [1, 2, 3];
        $right = [2, 4, 6];

        self::assertNumberValue('1.333333333333333333333333333333333333333333333333333333333333', Statistics::covariance($left, $right));
        self::assertNumberValue('2', Statistics::covariance($left, $right, true));
        self::assertNumberApproximately('1', Statistics::correlation($left, $right));
    }

    public function testMatrixStatisticsPreserveNumberElements(): void
    {
        $matrix = Statistics::covarianceMatrix(Matrix::of([[1, 2], [2, 4], [3, 6]]));
        $correlations = Statistics::correlationMatrix(Matrix::of([[1, 2], [2, 4], [3, 6]]));

        self::assertNumberValue('0.666666666666666666666666666666666666666666666666666666666667', Number::of($matrix->get(0, 0)));
        self::assertNumberValue('1.333333333333333333333333333333333333333333333333333333333333', Number::of($matrix->get(0, 1)));
        self::assertNumberApproximately('1', Number::of($correlations->get(0, 1)));
        self::assertInstanceOf(Number::class, $matrix->get(0, 0));
        self::assertInstanceOf(Number::class, $correlations->get(1, 1));
    }

    public function testMatrixStatisticsAreSymmetricWithVarianceDiagonals(): void
    {
        $data = Matrix::of([[1, 2, 4], [2, 4, 8], [3, 6, 12]]);
        $covariance = Statistics::covarianceMatrix($data);
        $correlation = Statistics::correlationMatrix($data);

        for ($row = 0; $row < $covariance->rows(); $row++) {
            for ($column = 0; $column < $covariance->columns(); $column++) {
                self::assertSame(
                    0,
                    $covariance->get($row, $column)->compare($covariance->get($column, $row))
                );
                self::assertSame(
                    0,
                    $correlation->get($row, $column)->compare($correlation->get($column, $row))
                );
            }

            self::assertSame(
                0,
                $covariance->get($row, $row)->compare(
                    Statistics::populationVariance($data->column($row))
                )
            );
            self::assertNumberApproximately('1', Number::of($correlation->get($row, $row)));
        }
    }

    public function testAllMathematicalStatisticsReturnNumber(): void
    {
        $data = [1, 2, 3, 4];
        $results = [
            Statistics::sum($data),
            Statistics::mean($data),
            Statistics::median($data),
            Statistics::mode([1, 1, 2]),
            Statistics::min($data),
            Statistics::max($data),
            Statistics::range($data),
            Statistics::populationVariance($data),
            Statistics::sampleVariance($data),
            Statistics::populationStandardDeviation($data),
            Statistics::sampleStandardDeviation($data),
            Statistics::meanAbsoluteDeviation($data),
            Statistics::medianAbsoluteDeviation($data),
            Statistics::interquartileRange($data),
            Statistics::coefficientOfVariation($data),
            Statistics::quantile($data, '0.5'),
            Statistics::percentile($data, 50),
            Statistics::quartile($data, 2),
            Statistics::decile($data, 5),
            Statistics::moment($data, 2),
            Statistics::centralMoment($data, 2),
            Statistics::skewness($data),
            Statistics::kurtosis($data),
            Statistics::excessKurtosis($data),
            Statistics::covariance($data, $data),
            Statistics::correlation($data, $data),
            Statistics::weightedMean($data, [1, 1, 1, 1]),
            Statistics::weightedVariance($data, [1, 1, 1, 1]),
        ];

        foreach ($results as $result) {
            self::assertInstanceOf(Number::class, $result);
        }
    }

    public function testWeightedStatisticsValidateWeights(): void
    {
        self::assertNumberValue('2.25', Statistics::weightedMean([1, 2, 3], [1, 1, 2]));
        self::assertNumberValue('0.6875', Statistics::weightedVariance([1, 2, 3], [1, 1, 2]));

        $this->expectException(InvalidArgumentException::class);
        Statistics::weightedMean([1, 2], [1, -1]);
    }

    public function testInvalidSamplesAndZeroVarianceAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Statistics::sampleVariance([1]);
    }

    public function testZeroVarianceCorrelationIsRejected(): void
    {
        $this->expectException(DivisionByZeroError::class);
        Statistics::correlation([1, 1], [2, 3]);
    }

    public function testMismatchedPairedDataIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Statistics::covariance([1, 2], [1]);
    }

    public function testCovarianceValidationMessagesMatchObservationRules(): void
    {
        try {
            Statistics::covariance([], [], false);
            self::fail('Expected empty population covariance to be rejected.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame(
                'Covariance requires at least one observation.',
                $exception->getMessage()
            );
        }

        try {
            Statistics::covariance([1], [1], true);
            self::fail('Expected sample covariance to require two observations.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame(
                'Sample covariance requires at least two observations.',
                $exception->getMessage()
            );
        }
    }

    private static function assertNumberValue(string $expected, Number $actual): void
    {
        self::assertInstanceOf(Number::class, $actual);
        self::assertSame($expected, $actual->value());
    }

    private static function assertNumberApproximately(string $expected, Number $actual): void
    {
        self::assertInstanceOf(Number::class, $actual);
        self::assertLessThanOrEqual(0, $actual->sub($expected)->abs()->compare('0.000000000001'));
    }
}