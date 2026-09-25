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
        $values = self::values($data);
        $sum = $values[0]->sub($values[0]);

        foreach ($values as $value) {
            $sum = $sum->add($value);
        }

        return $sum;
    }

    public static function mean(array|Vector $data): Number
    {
        $values = self::values($data);

        return self::sum($values)->div(Number::of(count($values)));
    }

    public static function median(array|Vector $data): Number
    {
        $values = self::sortedValues($data);
        $count = count($values);
        $middle = intdiv($count, 2);

        if (($count & 1) === 1) {
            return $values[$middle];
        }

        $one = $values[0]->one();

        return $values[$middle - 1]->add($values[$middle])->div($one->add($one));
    }

    public static function mode(array|Vector $data): Number
    {
        $values = self::values($data);
        /** @var list<array{value:Number,count:int}> $groups */
        $groups = [];
        $bestGroup = 0;
        $bestCount = 0;

        foreach ($values as $value) {
            $groupIndex = null;
            foreach ($groups as $index => $group) {
                if ($group['value']->compare($value) === 0) {
                    $groupIndex = $index;
                    break;
                }
            }

            if ($groupIndex === null) {
                $groups[] = ['value' => $value, 'count' => 1];
                $groupIndex = array_key_last($groups);
            } else {
                $groups[$groupIndex]['count']++;
                $groups[$groupIndex]['value'] = $value;
            }

            if ($groups[$groupIndex]['count'] > $bestCount) {
                $bestCount = $groups[$groupIndex]['count'];
                $bestGroup = $groupIndex;
            }
        }

        return $groups[$bestGroup]['value'];
    }

    public static function min(array|Vector $data): Number
    {
        $values = self::values($data);
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
        $values = self::values($data);
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
        return self::max($data)->sub(self::min($data));
    }

    public static function populationVariance(array|Vector $data): Number
    {
        $values = self::values($data);
        $mean = self::mean($values);
        $sum = $mean->sub($mean);

        foreach ($values as $value) {
            $sum = $sum->add($value->sub($mean)->pow(2));
        }

        return $sum->div(Number::of(count($values)));
    }

    public static function sampleVariance(array|Vector $data): Number
    {
        $values = self::values($data);
        if (count($values) < 2) {
            throw new InvalidArgumentException('Sample variance requires at least two observations.');
        }

        $mean = self::mean($values);
        $sum = $mean->sub($mean);

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
        $mean = self::mean($values);
        $sum = $mean->sub($mean);

        foreach ($values as $value) {
            $sum = $sum->add($value->sub($mean)->abs());
        }

        return $sum->div(Number::of(count($values)));
    }

    public static function medianAbsoluteDeviation(array|Vector $data): Number
    {
        $values = self::values($data);
        $median = self::median($values);
        $deviations = [];

        foreach ($values as $value) {
            $deviations[] = $value->sub($median)->abs();
        }

        return self::median($deviations);
    }

    public static function interquartileRange(array|Vector $data): Number
    {
        $values = self::sortedValues($data);
        $one = $values[0]->one();
        $three = $one->add($one)->add($one);
        $four = $one->add($one)->add($one)->add($one);

        return self::quantileFromSortedValues($values, $one->div($four)->mul($three))
            ->sub(self::quantileFromSortedValues($values, $one->div($four)));
    }

    /** Uses the population standard deviation, not the sample estimator. */
    public static function coefficientOfVariation(array|Vector $data): Number
    {
        $mean = self::mean($data);
        if ($mean->compare($mean->sub($mean)) === 0) {
            throw new DivisionByZeroError('Coefficient of variation is undefined for a zero mean.');
        }

        return self::populationStandardDeviation($data)->div($mean);
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
        $zero = $probability->sub($probability);
        $one = $probability->one();
        if (
            $probability->compare($zero) < 0
            || $probability->compare($one) > 0
        ) {
            throw new InvalidArgumentException('Quantile probability must be between 0 and 1.');
        }

        $lastIndex = count($values) - 1;
        $position = $probability->mul(Number::of($lastIndex));
        $lowerIndex = 0;
        $lowerPosition = $zero;

        while (
            $lowerIndex < $lastIndex
            && $position->compare($lowerPosition->add($one)) >= 0
        ) {
            $lowerIndex++;
            $lowerPosition = $lowerPosition->add($one);
        }

        if ($lowerIndex === $lastIndex) {
            return $values[$lastIndex];
        }

        $fraction = $position->sub($lowerPosition);
        return $values[$lowerIndex]->add(
            $values[$lowerIndex + 1]->sub($values[$lowerIndex])->mul($fraction)
        );
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
        $sum = $values[0]->sub($values[0]);

        foreach ($values as $value) {
            $sum = $sum->add($value->pow($order));
        }

        return $sum->div(Number::of(count($values)));
    }

    public static function centralMoment(array|Vector $data, int $order): Number
    {
        self::assertNonNegativeOrder($order);
        $values = self::values($data);
        $mean = self::mean($values);
        $sum = $mean->sub($mean);

        foreach ($values as $value) {
            $sum = $sum->add($value->sub($mean)->pow($order));
        }

        return $sum->div(Number::of(count($values)));
    }

    public static function skewness(array|Vector $data): Number
    {
        $standardDeviation = self::populationStandardDeviation($data);
        if ($standardDeviation->compare($standardDeviation->sub($standardDeviation)) === 0) {
            throw new DivisionByZeroError('Skewness is undefined for zero variance.');
        }

        return self::centralMoment($data, 3)->div($standardDeviation->pow(3));
    }

    public static function kurtosis(array|Vector $data): Number
    {
        $variance = self::populationVariance($data);
        if ($variance->compare($variance->sub($variance)) === 0) {
            throw new DivisionByZeroError('Kurtosis is undefined for zero variance.');
        }

        return self::centralMoment($data, 4)->div($variance->pow(2));
    }

    /** Returns Pearson kurtosis minus 3, without sample bias correction. */
    public static function excessKurtosis(array|Vector $data): Number
    {
        $kurtosis = self::kurtosis($data);
        $one = $kurtosis->one();

        return $kurtosis->sub($one->add($one)->add($one));
    }

    public static function covariance(
        array|Vector $left,
        array|Vector $right,
        bool $sample = false,
    ): Number {
        [$leftValues, $rightValues] = self::pairedValues($left, $right);
        $leftMean = self::mean($leftValues);
        $rightMean = self::mean($rightValues);
        $sum = $leftMean->sub($leftMean);

        foreach ($leftValues as $index => $value) {
            $sum = $sum->add(
                $value->sub($leftMean)->mul($rightValues[$index]->sub($rightMean))
            );
        }

        $divisor = $sample ? count($leftValues) - 1 : count($leftValues);
        if ($divisor < 1) {
            throw new InvalidArgumentException('Sample covariance requires at least two observations.');
        }

        return $sum->div(Number::of($divisor));
    }

    public static function correlation(array|Vector $left, array|Vector $right): Number
    {
        $covariance = self::covariance($left, $right);
        $leftDeviation = self::populationStandardDeviation($left);
        $rightDeviation = self::populationStandardDeviation($right);

        if (
            $leftDeviation->compare($leftDeviation->sub($leftDeviation)) === 0
            || $rightDeviation->compare($rightDeviation->sub($rightDeviation)) === 0
        ) {
            throw new DivisionByZeroError('Correlation is undefined for zero variance.');
        }

        return $covariance->div($leftDeviation->mul($rightDeviation));
    }

    public static function covarianceMatrix(Matrix $data, bool $sample = false): Matrix
    {
        $columns = [];
        for ($column = 0; $column < $data->columns(); $column++) {
            $columns[] = $data->column($column);
        }

        $rows = [];
        foreach ($columns as $left) {
            $row = [];
            foreach ($columns as $right) {
                $row[] = self::covariance($left, $right, $sample);
            }
            $rows[] = $row;
        }

        return Matrix::of($rows);
    }

    public static function correlationMatrix(Matrix $data): Matrix
    {
        $columns = [];
        for ($column = 0; $column < $data->columns(); $column++) {
            $columns[] = $data->column($column);
        }

        $rows = [];
        foreach ($columns as $left) {
            $row = [];
            foreach ($columns as $right) {
                $row[] = self::correlation($left, $right);
            }
            $rows[] = $row;
        }

        return Matrix::of($rows);
    }

    public static function weightedMean(array|Vector $data, array|Vector $weights): Number
    {
        [$values, $weights] = self::pairedValues($data, $weights);
        $weightedSum = $values[0]->sub($values[0]);
        $weightSum = $weights[0]->sub($weights[0]);

        foreach ($values as $index => $value) {
            self::assertNonNegativeWeight($weights[$index]);
            $weightedSum = $weightedSum->add($value->mul($weights[$index]));
            $weightSum = $weightSum->add($weights[$index]);
        }

        if ($weightSum->compare($weightSum->sub($weightSum)) === 0) {
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
        $zero = $values[0]->sub($values[0]);
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
        if ($weight->compare($weight->sub($weight)) < 0) {
            throw new InvalidArgumentException('Weights must be non-negative.');
        }
    }
}