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

    public static function sum(array|Vector $data): Number
    {
        return self::sumOf(self::values($data));
    }

    public static function mean(array|Vector $data): Number
    {
        $values = self::values($data);

        return self::meanOf($values);
    }

    public static function median(array|Vector $data): Number
    {
        $values = self::sortedValues($data);
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

    public static function mode(array|Vector $data): Number
    {
        $values = self::values($data);
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

    public static function min(array|Vector $data): Number
    {
        return self::minOf(self::values($data));
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

    public static function max(array|Vector $data): Number
    {
        return self::maxOf(self::values($data));
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

    public static function range(array|Vector $data): Number
    {
        $values = self::values($data);

        return self::maxOf($values)->sub(self::minOf($values));
    }

    public static function populationVariance(array|Vector $data): Number
    {
        $values = self::values($data);
        return self::populationVarianceOf($values);
    }

    public static function sampleVariance(array|Vector $data): Number
    {
        $values = self::values($data);
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

    public static function populationStandardDeviation(array|Vector $data): Number
    {
        return self::populationVariance($data)->sqrt();
    }

    public static function sampleStandardDeviation(array|Vector $data): Number
    {
        return self::sampleVariance($data)->sqrt();
    }

    public static function meanAbsoluteDeviation(array|Vector $data): Number
    {
        $values = self::values($data);
        $mean = self::meanOf($values);
        $sum = Number::of(0);

        foreach ($values as $value) {
            $sum = $sum->add($value->sub($mean)->abs());
        }

        return $sum->div(Number::of(count($values)));
    }

    public static function medianAbsoluteDeviation(array|Vector $data): Number
    {
        $values = self::values($data);
        $median = self::medianOfValues($values);
        $deviations = [];

        foreach ($values as $value) {
            $deviations[] = $value->sub($median)->abs();
        }

        return self::medianOfValues($deviations);
    }

    public static function interquartileRange(array|Vector $data): Number
    {
        $values = self::sortedValues($data);
        $three = Number::of(3);
        $four = Number::of(4);

        return self::quantileFromSortedValues($values, $three->div($four))
            ->sub(self::quantileFromSortedValues($values, Number::of(1)->div($four)));
    }

    /** Uses the population standard deviation, not the sample estimator. */
    public static function coefficientOfVariation(array|Vector $data): Number
    {
        $values = self::values($data);
        $mean = self::meanOf($values);
        if ($mean->compare(Number::of(0)) === 0) {
            throw new DivisionByZeroError('Coefficient of variation is undefined for a zero mean.');
        }

        return self::populationVarianceOf($values)->sqrt()->div($mean);
    }

    public static function quantile(
        array|Vector $data,
        int|float|string|Number $probability,
    ): Number {
        $values = self::sortedValues($data);
        return self::quantileFromSortedValues($values, Number::of($probability));
    }

    /** @param list<Number> $values */
    private static function quantileFromSortedValues(array $values, Number $probability): Number
    {
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
    ): Number {
        return self::quantile($data, Number::of($percentile)->div(Number::of(100)));
    }

    public static function quartile(array|Vector $data, int $quartile): Number
    {
        if ($quartile < 0 || $quartile > 4) {
            throw new InvalidArgumentException('Quartile must be between 0 and 4.');
        }

        return self::quantile($data, Number::of($quartile)->div(Number::of(4)));
    }

    public static function decile(array|Vector $data, int $decile): Number
    {
        if ($decile < 0 || $decile > 10) {
            throw new InvalidArgumentException('Decile must be between 0 and 10.');
        }

        return self::quantile($data, Number::of($decile)->div(Number::of(10)));
    }

    public static function moment(array|Vector $data, int $order): Number
    {
        self::assertNonNegativeOrder($order);
        $values = self::values($data);
        $sum = Number::of(0);

        foreach ($values as $value) {
            $sum = $sum->add($value->pow($order));
        }

        return $sum->div(Number::of(count($values)));
    }

    public static function centralMoment(array|Vector $data, int $order): Number
    {
        self::assertNonNegativeOrder($order);
        return self::centralMomentOf(self::values($data), $order);
    }

    public static function skewness(array|Vector $data): Number
    {
        $values = self::values($data);
        $standardDeviation = self::populationVarianceOf($values)->sqrt();
        if ($standardDeviation->compare(Number::of(0)) === 0) {
            throw new DivisionByZeroError('Skewness is undefined for zero variance.');
        }

        return self::centralMomentOf($values, 3)->div($standardDeviation->pow(3));
    }

    public static function kurtosis(array|Vector $data): Number
    {
        $values = self::values($data);
        $variance = self::populationVarianceOf($values);
        if ($variance->compare(Number::of(0)) === 0) {
            throw new DivisionByZeroError('Kurtosis is undefined for zero variance.');
        }

        return self::centralMomentOf($values, 4)->div($variance->pow(2));
    }

    /** Returns Pearson kurtosis minus 3, without sample bias correction. */
    public static function excessKurtosis(array|Vector $data): Number
    {
        return self::kurtosis($data)->sub(Number::of(3));
    }

    public static function covariance(
        array|Vector $left,
        array|Vector $right,
        bool $sample = false,
    ): Number {
        if (self::count($left) === 0 || self::count($right) === 0) {
            $message = $sample
                ? 'Sample covariance requires at least two observations.'
                : 'Covariance requires at least one observation.';
            throw new InvalidArgumentException($message);
        }

        [$leftValues, $rightValues] = self::pairedValues($left, $right);
        return self::covarianceOf($leftValues, $rightValues, $sample);
    }

    public static function correlation(array|Vector $left, array|Vector $right): Number
    {
        [$leftValues, $rightValues] = self::pairedValues($left, $right);
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

    public static function covarianceMatrix(Matrix $data, bool $sample = false): Matrix
    {
        $columns = self::matrixColumnValues($data);

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

    public static function correlationMatrix(Matrix $data): Matrix
    {
        $columns = self::matrixColumnValues($data);

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

    public static function weightedMean(array|Vector $data, array|Vector $weights): Number
    {
        [$values, $weights] = self::pairedValues($data, $weights);
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
    public static function weightedVariance(array|Vector $data, array|Vector $weights): Number
    {
        [$values, $weights] = self::pairedValues($data, $weights);
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
    private static function values(array|Vector $data): array
    {
        $values = $data instanceof Vector ? $data->values() : $data;

        if ($values === []) {
            throw new InvalidArgumentException('Statistics require at least one observation.');
        }

        return array_map(
            static fn (int|float|string|Number $value): Number => Number::of($value),
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
    private static function matrixColumnValues(Matrix $data): array
    {
        $columns = [];
        for ($column = 0; $column < $data->columns(); $column++) {
            $columns[] = self::values($data->column($column));
        }

        return $columns;
    }

    /** @return list<Number> */
    private static function sortedValues(array|Vector $data): array
    {
        $values = self::values($data);

        usort(
            $values,
            static fn (Number $left, Number $right): int => $left->compare($right)
        );

        return $values;
    }

    /** @return array{0:list<Number>,1:list<Number>} */
    private static function pairedValues(array|Vector $left, array|Vector $right): array
    {
        $leftValues = self::values($left);
        $rightValues = self::values($right);
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