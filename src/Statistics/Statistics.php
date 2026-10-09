<?php

declare(strict_types=1);

namespace Gauss\Statistics;

use DivisionByZeroError;
use Gauss\Linear\Matrix;
use Gauss\Linear\Vector;
use Gauss\Number\Number;
use InvalidArgumentException;

final class Statistics
{
    public static function count(array|Vector $data): int
    {
        return $data instanceof Vector ? $data->dimension() : count($data);
    }

    /**
     * @param bool $precision True keeps the default decimal backend; false selects floats where supported.
     */
    public static function sum(array|Vector $data, bool $precision = true): Number
    {
        return self::sumOf(self::values($data, $precision));
    }

    /** @param bool $precision True keeps the default decimal backend; false selects floats where supported. */
    public static function mean(array|Vector $data, bool $precision = true): Number
    {
        $values = self::values($data, $precision);

        return self::meanOf($values);
    }

    /** @param bool $precision True keeps the default decimal backend; false selects floats where supported. */
    public static function median(array|Vector $data, bool $precision = true): Number
    {
        $values = self::sortedValues($data, $precision);
        return self::medianFromSortedValues($values);
    }

    private static function medianOfValues(array $values): Number
    {
        usort(
            $values,
            static fn (Number $left, Number $right): int => $left->compare($right)
        );

        return self::medianFromSortedValues($values);
    }

    /** @param list<Number> $values */
    private static function medianFromSortedValues(array $values): Number
    {
        $count = count($values);
        $middle = intdiv($count, 2);

        if (($count & 1) === 1) {
            return $values[$middle];
        }

        return $values[$middle - 1]->add($values[$middle])->div(Number::of(2));
    }

    /** @param bool $precision True keeps the default decimal backend; false selects floats where supported. */
    public static function mode(array|Vector $data, bool $precision = true): Number
    {
        $values = self::values($data, $precision);
        $groups = [];
        $mode = $values[0];
        $bestCount = 0;

        foreach ($values as $value) {
            // Number::value() is canonical, so numeric equality has one stable key.
            $key = 'number:' . $value->value();
            $groups[$key] = ($groups[$key] ?? 0) + 1;

            if ($groups[$key] > $bestCount) {
                $mode = $value;
                $bestCount = $groups[$key];
            }
        }

        return $mode;
    }

    /** @param bool $precision True keeps the default decimal backend; false selects floats where supported. */
    public static function min(array|Vector $data, bool $precision = true): Number
    {
        return self::minOf(self::values($data, $precision));
    }

    /** @param list<Number> $values */
    private static function minOf(array $values): Number
    {
        $minimum = $values[0];

        foreach ($values as $value) {
            if ($value->compare($minimum) < 0) {
                $minimum = $value;
            }
        }

        return $minimum;
    }

    /** @param bool $precision True keeps the default decimal backend; false selects floats where supported. */
    public static function max(array|Vector $data, bool $precision = true): Number
    {
        return self::maxOf(self::values($data, $precision));
    }

    /** @param list<Number> $values */
    private static function maxOf(array $values): Number
    {
        $maximum = $values[0];

        foreach ($values as $value) {
            if ($value->compare($maximum) > 0) {
                $maximum = $value;
            }
        }

        return $maximum;
    }

    /** @param bool $precision True keeps the default decimal backend; false selects floats where supported. */
    public static function range(array|Vector $data, bool $precision = true): Number
    {
        $values = self::values($data, $precision);

        return self::maxOf($values)->sub(self::minOf($values));
    }

    /** @param bool $precision True keeps the default decimal backend; false selects floats where supported. */
    public static function populationVariance(array|Vector $data, bool $precision = true): Number
    {
        $values = self::values($data, $precision);
        return self::populationVarianceOf($values);
    }

    /** @param bool $precision True keeps the default decimal backend; false selects floats where supported. */
    public static function sampleVariance(array|Vector $data, bool $precision = true): Number
    {
        $values = self::values($data, $precision);
        if (count($values) < 2) {
            throw new InvalidArgumentException('Sample variance requires at least two observations.');
        }

        $mean = self::meanOf($values);
        $sum = Number::of(0);

        foreach ($values as $value) {
            $sum = $sum->add($value->sub($mean)->pow(2));
        }

        return $sum->div(Number::of(count($values) - 1));
    }

    /** @param bool $precision True keeps the default decimal backend; false selects floats where supported. */
    public static function populationStandardDeviation(array|Vector $data, bool $precision = true): Number
    {
        return self::populationVariance($data, $precision)->sqrt();
    }

    /** @param bool $precision True keeps the default decimal backend; false selects floats where supported. */
    public static function sampleStandardDeviation(array|Vector $data, bool $precision = true): Number
    {
        return self::sampleVariance($data, $precision)->sqrt();
    }

    /** @param bool $precision True keeps the default decimal backend; false selects floats where supported. */
    public static function meanAbsoluteDeviation(array|Vector $data, bool $precision = true): Number
    {
        $values = self::values($data, $precision);
        $mean = self::meanOf($values);
        $sum = Number::of(0);

        foreach ($values as $value) {
            $sum = $sum->add($value->sub($mean)->abs());
        }

        return $sum->div(Number::of(count($values)));
    }

    /** @param bool $precision True keeps the default decimal backend; false selects floats where supported. */
    public static function medianAbsoluteDeviation(array|Vector $data, bool $precision = true): Number
    {
        $values = self::values($data, $precision);
        $median = self::medianOfValues($values);
        $deviations = [];

        foreach ($values as $value) {
            $deviations[] = $value->sub($median)->abs();
        }

        return self::medianOfValues($deviations);
    }

    /** @param bool $precision True keeps the default decimal backend; false selects floats where supported. */
    public static function interquartileRange(array|Vector $data, bool $precision = true): Number
    {
        $values = self::sortedValues($data, $precision);
        $three = Number::of(3);
        $four = Number::of(4);

        return self::quantileFromSortedValues($values, $three->div($four))
            ->sub(self::quantileFromSortedValues($values, Number::of(1)->div($four)));
    }

    /** Uses the population standard deviation, not the sample estimator. */
    /** @param bool $precision True keeps the default decimal backend; false selects floats where supported. */
    public static function coefficientOfVariation(array|Vector $data, bool $precision = true): Number
    {
        $values = self::values($data, $precision);
        $mean = self::meanOf($values);
        if ($mean->compare(Number::of(0)) === 0) {
            throw new DivisionByZeroError('Coefficient of variation is undefined for a zero mean.');
        }

        return self::populationVarianceOf($values)->sqrt()->div($mean);
    }

    public static function quantile(
        array|Vector $data,
        int|float|string|Number $probability,
        bool $precision = true,
    ): Number {
        $values = self::sortedValues($data, $precision);
        $quantileProbability = Number::of($probability);
        $quantileProbability = $quantileProbability->withBackend(! $precision);
        return self::quantileFromSortedValues($values, $quantileProbability);
    }

    /** @param list<Number> $values */
    private static function quantileFromSortedValues(array $values, Number $probability): Number
    {
        foreach ($values as $value) {
            if ($value->usesFloatBackend() && ! $probability->usesFloatBackend()) {
                $probability = $probability->offPrecision();
                break;
            }
        }

        if (
            $probability->compare(Number::of(0)) < 0
            || $probability->compare(Number::of(1)) > 0
        ) {
            throw new InvalidArgumentException('Quantile probability must be between 0 and 1.');
        }

        $lastIndex = count($values) - 1;
        $position = $probability->mul(Number::of($lastIndex));
        $lowerIndex = self::floorIndex($position, $lastIndex);

        if ($lowerIndex === $lastIndex) {
            return $values[$lastIndex];
        }

        $fraction = $position->sub(Number::of($lowerIndex));
        return $values[$lowerIndex]->add(
            $values[$lowerIndex + 1]->sub($values[$lowerIndex])->mul($fraction)
        );
    }

    private static function floorIndex(Number $position, int $lastIndex): int
    {
        $lower = 0;
        $upper = $lastIndex;

        while ($lower < $upper) {
            $middle = $lower + intdiv($upper - $lower, 2) + 1;

            if ($position->compare(Number::of($middle)) >= 0) {
                $lower = $middle;
            } else {
                $upper = $middle - 1;
            }
        }

        return $lower;
    }

    public static function percentile(
        array|Vector $data,
        int|float|string|Number $percentile,
        bool $precision = true,
    ): Number {
        $value = Number::of($percentile)->withBackend(! $precision);
        return self::quantile(
            $data,
            $value->div(Number::of(100)),
            $precision
        );
    }

    /** @param bool $precision True keeps the default decimal backend; false selects floats where supported. */
    public static function quartile(array|Vector $data, int $quartile, bool $precision = true): Number
    {
        if ($quartile < 0 || $quartile > 4) {
            throw new InvalidArgumentException('Quartile must be between 0 and 4.');
        }

        $value = Number::of($quartile)->withBackend(! $precision);
        return self::quantile($data, $value->div(Number::of(4)), $precision);
    }

    /** @param bool $precision True keeps the default decimal backend; false selects floats where supported. */
    public static function decile(array|Vector $data, int $decile, bool $precision = true): Number
    {
        if ($decile < 0 || $decile > 10) {
            throw new InvalidArgumentException('Decile must be between 0 and 10.');
        }

        $value = Number::of($decile)->withBackend(! $precision);
        return self::quantile($data, $value->div(Number::of(10)), $precision);
    }

    /** @param bool $precision True keeps the default decimal backend; false selects floats where supported. */
    public static function moment(array|Vector $data, int $order, bool $precision = true): Number
    {
        self::assertNonNegativeOrder($order);
        $values = self::values($data, $precision);
        $sum = Number::of(0);

        foreach ($values as $value) {
            $sum = $sum->add($value->pow($order));
        }

        return $sum->div(Number::of(count($values)));
    }

    /** @param bool $precision True keeps the default decimal backend; false selects floats where supported. */
    public static function centralMoment(array|Vector $data, int $order, bool $precision = true): Number
    {
        self::assertNonNegativeOrder($order);
        return self::centralMomentOf(self::values($data, $precision), $order);
    }

    /** @param bool $precision True keeps the default decimal backend; false selects floats where supported. */
    public static function skewness(array|Vector $data, bool $precision = true): Number
    {
        $values = self::values($data, $precision);
        $standardDeviation = self::populationVarianceOf($values)->sqrt();
        if ($standardDeviation->compare(Number::of(0)) === 0) {
            throw new DivisionByZeroError('Skewness is undefined for zero variance.');
        }

        return self::centralMomentOf($values, 3)->div($standardDeviation->pow(3));
    }

    /** @param bool $precision True keeps the default decimal backend; false selects floats where supported. */
    public static function kurtosis(array|Vector $data, bool $precision = true): Number
    {
        $values = self::values($data, $precision);
        $variance = self::populationVarianceOf($values);
        if ($variance->compare(Number::of(0)) === 0) {
            throw new DivisionByZeroError('Kurtosis is undefined for zero variance.');
        }

        return self::centralMomentOf($values, 4)->div($variance->pow(2));
    }

    /** Returns Pearson kurtosis minus 3, without sample bias correction. */
    /** @param bool $precision True keeps the default decimal backend; false selects floats where supported. */
    public static function excessKurtosis(array|Vector $data, bool $precision = true): Number
    {
        return self::kurtosis($data, $precision)->sub(Number::of(3));
    }

    public static function covariance(
        array|Vector $left,
        array|Vector $right,
        bool $sample = false,
        bool $precision = true,
    ): Number {
        if (self::count($left) === 0 || self::count($right) === 0) {
            $message = $sample
                ? 'Sample covariance requires at least two observations.'
                : 'Covariance requires at least one observation.';
            throw new InvalidArgumentException($message);
        }

        [$leftValues, $rightValues] = self::pairedValues($left, $right, $precision);
        return self::covarianceOf($leftValues, $rightValues, $sample);
    }

    public static function correlation(
        array|Vector $left,
        array|Vector $right,
        bool $precision = true,
    ): Number
    {
        [$leftValues, $rightValues] = self::pairedValues($left, $right, $precision);
        $covariance = self::covarianceOf($leftValues, $rightValues, false);
        $leftDeviation = self::populationVarianceOf($leftValues)->sqrt();
        $rightDeviation = self::populationVarianceOf($rightValues)->sqrt();

        if (
            $leftDeviation->compare(Number::of(0)) === 0
            || $rightDeviation->compare(Number::of(0)) === 0
        ) {
            throw new DivisionByZeroError('Correlation is undefined for zero variance.');
        }

        return $covariance->div($leftDeviation->mul($rightDeviation));
    }

    public static function covarianceMatrix(
        Matrix $data,
        bool $sample = false,
        bool $precision = true,
    ): Matrix
    {
        $columns = self::matrixColumnValues($data, $precision);

        $rows = [];
        foreach ($columns as $left) {
            $row = [];
            foreach ($columns as $right) {
                $row[] = self::covarianceOf($left, $right, $sample);
            }
            $rows[] = $row;
        }

        return Matrix::of($rows);
    }

    public static function correlationMatrix(Matrix $data, bool $precision = true): Matrix
    {
        $columns = self::matrixColumnValues($data, $precision);

        $rows = [];
        foreach ($columns as $left) {
            $row = [];
            foreach ($columns as $right) {
                $row[] = self::correlationOf($left, $right);
            }
            $rows[] = $row;
        }

        return Matrix::of($rows);
    }

    /** @param bool $precision True keeps the default decimal backend; false selects floats where supported. */
    public static function weightedMean(
        array|Vector $data,
        array|Vector $weights,
        bool $precision = true,
    ): Number
    {
        [$values, $weights] = self::pairedValues($data, $weights, $precision);
        $weightedSum = Number::of(0);
        $weightSum = Number::of(0);

        foreach ($values as $index => $value) {
            self::assertNonNegativeWeight($weights[$index]);
            $weightedSum = $weightedSum->add($value->mul($weights[$index]));
            $weightSum = $weightSum->add($weights[$index]);
        }

        if ($weightSum->compare(Number::of(0)) === 0) {
            throw new DivisionByZeroError('Weighted mean requires a non-zero total weight.');
        }

        return $weightedSum->div($weightSum);
    }

    /**
     * Computes weighted population variance using the denominator sum(w_i).
     */
    /** @param bool $precision True keeps the default decimal backend; false selects floats where supported. */
    public static function weightedVariance(
        array|Vector $data,
        array|Vector $weights,
        bool $precision = true,
    ): Number
    {
        [$values, $weights] = self::pairedValues($data, $weights, $precision);
        $zero = Number::of(0);
        $weightedSum = $zero;
        $weightSum = $zero;
        $weightedMeanNumerator = $zero;

        foreach ($values as $index => $value) {
            self::assertNonNegativeWeight($weights[$index]);
            $weightedMeanNumerator = $weightedMeanNumerator->add($value->mul($weights[$index]));
            $weightSum = $weightSum->add($weights[$index]);
        }

        if ($weightSum->compare($zero) === 0) {
            throw new DivisionByZeroError('Weighted variance requires a non-zero total weight.');
        }

        $mean = $weightedMeanNumerator->div($weightSum);
        foreach ($values as $index => $value) {
            $weightedSum = $weightedSum->add(
                $value->sub($mean)->pow(2)->mul($weights[$index])
            );
        }

        return $weightedSum->div($weightSum);
    }

    /** @return list<Number> */
    private static function values(array|Vector $data, bool $precision = true): array
    {
        $values = $data instanceof Vector ? $data->values() : $data;

        if ($values === []) {
            throw new InvalidArgumentException('Statistics require at least one observation.');
        }

        return array_map(
            static function (int|float|string|Number $value) use ($precision): Number {
                return Number::of($value)->withBackend(! $precision);
            },
            array_values($values)
        );
    }

    /** @param list<Number> $values */
    private static function sumOf(array $values): Number
    {
        $sum = Number::of(0);

        foreach ($values as $value) {
            $sum = $sum->add($value);
        }

        return $sum;
    }

    /** @param list<Number> $values */
    private static function meanOf(array $values): Number
    {
        return self::sumOf($values)->div(Number::of(count($values)));
    }

    /** @param list<Number> $values */
    private static function populationVarianceOf(array $values): Number
    {
        $mean = self::meanOf($values);
        $sum = Number::of(0);

        foreach ($values as $value) {
            $sum = $sum->add($value->sub($mean)->pow(2));
        }

        return $sum->div(Number::of(count($values)));
    }

    /** @param list<Number> $values */
    private static function centralMomentOf(array $values, int $order): Number
    {
        $mean = self::meanOf($values);
        $sum = Number::of(0);

        foreach ($values as $value) {
            $sum = $sum->add($value->sub($mean)->pow($order));
        }

        return $sum->div(Number::of(count($values)));
    }

    /** @param list<Number> $leftValues @param list<Number> $rightValues */
    private static function covarianceOf(
        array $leftValues,
        array $rightValues,
        bool $sample
    ): Number {
        $leftMean = self::meanOf($leftValues);
        $rightMean = self::meanOf($rightValues);
        $sum = Number::of(0);

        foreach ($leftValues as $index => $value) {
            $sum = $sum->add(
                $value->sub($leftMean)->mul($rightValues[$index]->sub($rightMean))
            );
        }

        $divisor = $sample ? count($leftValues) - 1 : count($leftValues);
        if ($divisor < 1) {
            $message = $sample
                ? 'Sample covariance requires at least two observations.'
                : 'Covariance requires at least one observation.';
            throw new InvalidArgumentException($message);
        }

        return $sum->div(Number::of($divisor));
    }

    /** @param list<Number> $leftValues @param list<Number> $rightValues */
    private static function correlationOf(array $leftValues, array $rightValues): Number
    {
        $covariance = self::covarianceOf($leftValues, $rightValues, false);
        $leftDeviation = self::populationVarianceOf($leftValues)->sqrt();
        $rightDeviation = self::populationVarianceOf($rightValues)->sqrt();

        if (
            $leftDeviation->compare(Number::of(0)) === 0
            || $rightDeviation->compare(Number::of(0)) === 0
        ) {
            throw new DivisionByZeroError('Correlation is undefined for zero variance.');
        }

        return $covariance->div($leftDeviation->mul($rightDeviation));
    }

    /** @return list<list<Number>> */
    private static function matrixColumnValues(Matrix $data, bool $precision = true): array
    {
        $columns = [];
        for ($column = 0; $column < $data->columns(); $column++) {
            $columns[] = self::values($data->column($column), $precision);
        }

        return $columns;
    }

    /** @return list<Number> */
    private static function sortedValues(array|Vector $data, bool $precision = true): array
    {
        $values = self::values($data, $precision);

        usort(
            $values,
            static fn (Number $left, Number $right): int => $left->compare($right)
        );

        return $values;
    }

    /** @return array{0:list<Number>,1:list<Number>} */
    private static function pairedValues(
        array|Vector $left,
        array|Vector $right,
        bool $precision = true,
    ): array
    {
        $leftValues = self::values($left, $precision);
        $rightValues = self::values($right, $precision);
        if (count($leftValues) !== count($rightValues)) {
            throw new InvalidArgumentException('Statistical data must have matching lengths.');
        }

        return [$leftValues, $rightValues];
    }

    private static function assertNonNegativeOrder(int $order): void
    {
        if ($order < 0) {
            throw new InvalidArgumentException('Moment order must be non-negative.');
        }
    }

    private static function assertNonNegativeWeight(Number $weight): void
    {
        if ($weight->compare(Number::of(0)) < 0) {
            throw new InvalidArgumentException('Weights must be non-negative.');
        }
    }
}