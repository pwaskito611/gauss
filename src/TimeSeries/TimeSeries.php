<?php

declare(strict_types=1);

namespace Gauss\TimeSeries;

use Gauss\Linear\Matrix;
use Gauss\Linear\Solution\UniqueSolution;
use Gauss\Linear\Vector;
use Gauss\Number\Number;
use Gauss\Statistics\Statistics;
use InvalidArgumentException;

final class TimeSeries
{
    /** @var list<Observation> */
    private readonly array $observations;

    /**
     * @param list<int|float|string|Number> $values
     */
    private function __construct(array $values)
    {
        if ($values === []) {
            throw new InvalidArgumentException('Time series must contain at least one observation.');
        }

        $observations = [];
        foreach ($values as $index => $value) {
            $observations[] = new Observation($index, Number::of($value));
        }

        $this->observations = $observations;
    }

    /**
     * @param list<int|float|string|Number> $values
     */
    public static function of(array $values): self
    {
        return new self($values);
    }

    /** @return list<Observation> */
    public function observations(): array
    {
        return $this->observations;
    }

    public function count(): int
    {
        return count($this->observations);
    }

    public function first(): Observation
    {
        return $this->observations[0];
    }

    public function last(): Observation
    {
        return $this->observations[count($this->observations) - 1];
    }

    public function valueAt(int $index): Number
    {
        if ($index < 0 || $index >= $this->count()) {
            throw new InvalidArgumentException('Observation index is outside the time series.');
        }

        return $this->observations[$index]->value();
    }

    public function map(callable $transform): self
    {
        return new self(array_map(
            static fn (Observation $observation): Number => Number::of($transform($observation->value())),
            $this->observations
        ));
    }

    public function slice(int $start, ?int $length = null): self
    {
        if ($start < 0 || $start >= $this->count()) {
            throw new InvalidArgumentException('Slice start index is invalid.');
        }

        if ($length !== null && $length <= 0) {
            throw new InvalidArgumentException('Slice length must be positive.');
        }

        $end = $length === null ? $this->count() : min($this->count(), $start + $length);
        $values = [];
        for ($index = $start; $index < $end; $index++) {
            $values[] = $this->observations[$index]->value();
        }

        return new self($values);
    }

    /** @return list<Number> */
    public function values(): array
    {
        return array_map(
            static fn (Observation $observation): Number => $observation->value(),
            $this->observations
        );
    }

    public function lag(int $lag): self
    {
        if ($lag < 0) {
            throw new InvalidArgumentException('Lag must be non-negative.');
        }

        if ($lag >= $this->count()) {
            throw new InvalidArgumentException('Lag must be smaller than the number of observations.');
        }

        $values = [];
        for ($index = $lag; $index < $this->count(); $index++) {
            $values[] = $this->observations[$index - $lag]->value();
        }

        return new self($values);
    }

    public function difference(int $order = 1): self
    {
        if ($order < 1) {
            throw new InvalidArgumentException('Difference order must be at least 1.');
        }

        $series = $this;
        for ($iteration = 0; $iteration < $order; $iteration++) {
            if ($series->count() < 2) {
                throw new InvalidArgumentException('Difference order must be smaller than the number of observations.');
            }

            $values = [];
            for ($index = 1; $index < $series->count(); $index++) {
                $values[] = $series->observations[$index]->value()->sub($series->observations[$index - 1]->value());
            }
            $series = new self($values);
        }

        return $series;
    }

    public function variance(): Number
    {
        return Statistics::populationVariance($this->values());
    }

    public function isConstant(): bool
    {
        if ($this->count() < 2) {
            return true;
        }

        $first = $this->observations[0]->value();
        foreach ($this->observations as $observation) {
            if ($observation->value()->compare($first) !== 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Computes the autocorrelation using the full-series sum of squared deviations as the normalization denominator.
     * This is the biased definition with denominator n, not n-k.
     */
    public function acf(int $lag): Number
    {
        if ($lag < 0) {
            throw new InvalidArgumentException('ACF lag must be non-negative.');
        }

        if ($lag >= $this->count()) {
            throw new InvalidArgumentException('ACF lag exceeds the number of observations.');
        }

        if ($this->count() < 2) {
            return Number::of(1);
        }

        $values = $this->values();
        $mean = Statistics::mean($values);
        $variance = Statistics::populationVariance($values);

        if ($variance->compare(Number::of(0)) === 0) {
            return $lag === 0 ? Number::of(1) : Number::of(0);
        }

        if ($lag === 0) {
            return Number::of(1);
        }

        $numerator = Number::of(0);
        $denominator = Number::of(0);

        foreach ($values as $index => $value) {
            $centered = $value->sub($mean);
            $denominator = $denominator->add($centered->pow(2));
            if ($index >= $lag) {
                $lagged = $values[$index - $lag]->sub($mean);
                $numerator = $numerator->add($centered->mul($lagged));
            }
        }

        return $numerator->div($denominator);
    }

    /**
     * Solves the lagged normal equations for the partial autocorrelation at the requested lag.
     * Singular designs raise an InvalidArgumentException instead of silently returning zero.
     */
    public function pacf(int $lag): Number
    {
        if ($lag < 1) {
            throw new InvalidArgumentException('PACF lag must be at least 1.');
        }

        if ($lag >= $this->count()) {
            throw new InvalidArgumentException('PACF lag exceeds the number of observations.');
        }

        $values = $this->values();
        $mean = Statistics::mean($values);
        $centered = array_map(
            static fn (Number $value): Number => $value->sub($mean),
            $values,
        );

        $y = [];
        $design = [];
        for ($index = $lag; $index < $this->count(); $index++) {
            $y[] = $centered[$index];
            $row = [];
            for ($offset = 1; $offset <= $lag; $offset++) {
                $row[] = $centered[$index - $offset];
            }
            $design[] = $row;
        }

        if ($y === []) {
            throw new InvalidArgumentException('PACF calculation is undefined for the provided lag.');
        }

        $estimated = self::solveNormalEquations($y, $design);
        return $estimated[count($estimated) - 1];
    }

    public function toArray(): array
    {
        return array_map(
            static fn (Observation $observation): string => $observation->value()->value(),
            $this->observations
        );
    }

    /**
     * @param list<Number> $y
     * @param list<list<Number>> $design
     * @return list<Number>
     */
    private static function solveNormalEquations(array $y, array $design): array
    {
        $rowCount = count($y);
        $columnCount = count($design[0]);

        $xtx = [];
        for ($row = 0; $row < $columnCount; $row++) {
            $xtx[$row] = [];
            for ($column = 0; $column < $columnCount; $column++) {
                $value = Number::of(0);
                for ($index = 0; $index < $rowCount; $index++) {
                    $value = $value->add($design[$index][$row]->mul($design[$index][$column]));
                }
                $xtx[$row][$column] = $value;
            }
        }

        $rhs = [];
        for ($column = 0; $column < $columnCount; $column++) {
            $value = Number::of(0);
            for ($index = 0; $index < $rowCount; $index++) {
                $value = $value->add($design[$index][$column]->mul($y[$index]));
            }
            $rhs[] = $value;
        }

        $solution = \Gauss\Linear\LinearSystem::of(
            Matrix::of($xtx),
            Vector::of(...$rhs)
        )->solve();

        if (! $solution instanceof UniqueSolution) {
            throw new InvalidArgumentException('PACF regression does not have a unique solution.');
        }

        return $solution->vector()->values();
    }
}
