<?php

declare(strict_types=1);

namespace Gauss\Tests\Statistics;

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
        $result = Statistics::mean(['1/2', '3/2']);

        self::assertInstanceOf(Number::class, $result);
        self::assertSame('1', $result->value());
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

        self::assertNumberValue('5/4', Statistics::populationVariance($data));
        self::assertNumberValue('5/3', Statistics::sampleVariance($data));
        self::assertNumberValue('1', Statistics::meanAbsoluteDeviation($data));
        self::assertNumberValue('1', Statistics::medianAbsoluteDeviation($data));
        self::assertNumberValue('3/2', Statistics::interquartileRange($data));
        self::assertSame(0, Statistics::coefficientOfVariation($data)->compare('0.44721359549995793928183473374625524708812367192231'));
        self::assertSame(0, Statistics::populationStandardDeviation($data)->compare('1.11803398874989484820458683436563811772030917980577'));
        self::assertSame(0, Statistics::sampleStandardDeviation($data)->compare('1.29099444873580562839308846659413320361097390176387'));
    }

    public function testQuantilesUseLinearInterpolation(): void
    {
        $data = [1, 2, 3, 4];

        self::assertNumberValue('7/4', Statistics::quantile($data, '1/4'));
        self::assertNumberValue('13/4', Statistics::percentile($data, 75));
        self::assertNumberValue('7/4', Statistics::quartile($data, 1));
        self::assertNumberValue('5/2', Statistics::decile($data, 5));
    }

    public function testQuantilesRejectInvalidProbabilityAndLabels(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Statistics::quantile([1, 2], '3/2');
    }

    public function testMomentsAndShapeStatistics(): void
    {
        $data = [1, 2, 3];

        self::assertNumberValue('14/3', Statistics::moment($data, 2));
        self::assertNumberValue('2/3', Statistics::centralMoment($data, 2));
        self::assertSame(0, Statistics::skewness($data)->compare(0));
        self::assertNumberValue('3/2', Statistics::kurtosis($data));
        self::assertNumberValue('-5/2', Statistics::excessKurtosis($data));
    }

    public function testDependenceStatisticsAndSampleCovariance(): void
    {
        $left = [1, 2, 3];
        $right = [2, 4, 6];

        self::assertNumberValue('4/3', Statistics::covariance($left, $right));
        self::assertNumberValue('2', Statistics::covariance($left, $right, true));
        self::assertSame(0, Statistics::correlation($left, $right)->compare('0.99999999999999999999999999999999999999999999999999'));
    }

    public function testMatrixStatisticsPreserveNumberElements(): void
    {
        $matrix = Statistics::covarianceMatrix(Matrix::of([[1, 2], [2, 4], [3, 6]]));
        $correlations = Statistics::correlationMatrix(Matrix::of([[1, 2], [2, 4], [3, 6]]));

        self::assertNumberValue('2/3', Number::of($matrix->get(0, 0)));
        self::assertNumberValue('4/3', Number::of($matrix->get(0, 1)));
        self::assertSame(0, Number::of($correlations->get(0, 1))->compare('0.99999999999999999999999999999999999999999999999999'));
        self::assertInstanceOf(Number::class, $matrix->get(0, 0));
        self::assertInstanceOf(Number::class, $correlations->get(1, 1));
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
            Statistics::quantile($data, '1/2'),
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
        self::assertNumberValue('9/4', Statistics::weightedMean([1, 2, 3], [1, 1, 2]));
        self::assertNumberValue('11/16', Statistics::weightedVariance([1, 2, 3], [1, 1, 2]));

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

    private static function assertNumberValue(string $expected, Number $actual): void
    {
        self::assertInstanceOf(Number::class, $actual);
        self::assertSame($expected, $actual->value());
    }
}