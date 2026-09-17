<?php

declare(strict_types=1);

namespace Gauss\TimeSeries\Model;

use Gauss\Linear\LinearSystem;
use Gauss\Linear\Matrix;
use Gauss\Linear\Vector;
use Gauss\Number\Number;
use Gauss\Statistics\Statistics;
use Gauss\TimeSeries\TimeSeries;
use InvalidArgumentException;

final class MA
{
    /** @var list<Number> */
    private readonly array $coefficients;

    /**
     * @param list<Number> $coefficients
     */
    private function __construct(
        private readonly int $order,
        array $coefficients,
        private readonly Number $mean,
        private readonly TimeSeries $series,
    ) {
        $this->coefficients = $coefficients;
    }

    public static function fit(TimeSeries $series, int $order): self
    {
        if ($order < 1) {
            throw new InvalidArgumentException('MA order must be at least 1.');
        }

        if ($series->count() <= $order) {
            throw new InvalidArgumentException('MA fitting requires more observations than the model order.');
        }

        $values = $series->values();
        $mean = Statistics::mean($values);
        $coefficients = array_fill(0, $order, Number::of(0));
        $residuals = [];

        foreach ($values as $index => $value) {
            $residuals[$index] = $value->sub($mean);
        }

        for ($iteration = 0; $iteration < 10; $iteration++) {
            $rows = [];
            $targets = [];

            for ($index = $order; $index < $series->count(); $index++) {
                $target = $values[$index]->sub($mean);
                $row = [];
                for ($lag = 1; $lag <= $order; $lag++) {
                    $previousIndex = $index - $lag;
                    $row[] = $previousIndex >= 0 ? $residuals[$previousIndex] : Number::of(0);
                }
                $rows[] = $row;
                $targets[] = $target;
            }

            if ($rows === []) {
                break;
            }

            $xtx = [];
            $columnCount = count($rows[0]);
            for ($row = 0; $row < $columnCount; $row++) {
                $current = [];
                for ($column = 0; $column < $columnCount; $column++) {
                    $sum = Number::of(0);
                    foreach ($rows as $entry) {
                        $sum = $sum->add($entry[$row]->mul($entry[$column]));
                    }
                    $current[] = $sum;
                }
                $xtx[] = $current;
            }

            $xty = [];
            for ($column = 0; $column < $columnCount; $column++) {
                $sum = Number::of(0);
                foreach ($rows as $index => $row) {
                    $sum = $sum->add($row[$column]->mul($targets[$index]));
                }
                $xty[] = $sum;
            }

            $solution = LinearSystem::of(
                Matrix::of($xtx),
                Vector::of(...$xty)
            )->solve();

            if (! $solution->hasSolution()) {
                break;
            }

            $coefficients = $solution->vector()->values();
            foreach ($values as $index => $value) {
                $residual = $value->sub($mean);
                for ($lag = 1; $lag <= $order; $lag++) {
                    if ($index - $lag >= 0) {
                        $residual = $residual->sub($coefficients[$lag - 1]->mul($residuals[$index - $lag]));
                    }
                }
                $residuals[$index] = $residual;
            }
        }

        return new self($order, $coefficients, $mean, $series);
    }

    public function order(): int
    {
        return $this->order;
    }

    /** @return list<Number> */
    public function coefficients(): array
    {
        return $this->coefficients;
    }

    public function mean(): Number
    {
        return $this->mean;
    }

    public function predict(int $horizon): TimeSeries
    {
        if ($horizon < 1) {
            throw new InvalidArgumentException('Prediction horizon must be at least 1.');
        }

        $history = $this->series->values();
        $residuals = $this->residuals()->values();

        for ($step = 0; $step < $horizon; $step++) {
            $forecast = $this->mean;
            for ($lag = 1; $lag <= $this->order; $lag++) {
                $residualIndex = count($residuals) - $lag;
                if ($residualIndex >= 0) {
                    $forecast = $forecast->add($this->coefficients[$lag - 1]->mul($residuals[$residualIndex]));
                }
            }
            $history[] = $forecast;
            $residuals[] = Number::of(0);
        }

        return TimeSeries::of($history);
    }

    public function residuals(): TimeSeries
    {
        $values = $this->series->values();
        $residuals = [];

        foreach ($values as $index => $value) {
            $residual = $value->sub($this->mean);
            for ($lag = 1; $lag <= $this->order; $lag++) {
                if ($index - $lag >= 0) {
                    $residual = $residual->sub($this->coefficients[$lag - 1]->mul($residuals[$index - $lag]));
                }
            }
            $residuals[] = $residual;
        }

        return TimeSeries::of($residuals);
    }
}
