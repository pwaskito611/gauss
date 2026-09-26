<?php

declare(strict_types=1);

namespace Gauss\TimeSeries\Model;

use Gauss\Linear\LinearSystem;
use Gauss\Linear\Matrix;
use Gauss\Linear\Vector;
use Gauss\Number\Number;
use Gauss\TimeSeries\TimeSeries;
use InvalidArgumentException;

final class AR
{
    /** @var list<Number> */
    private readonly array $coefficients;

    /**
     * @param list<Number> $coefficients
     */
    private function __construct(
        private readonly int $order,
        array $coefficients,
        private readonly Number $intercept,
        private readonly TimeSeries $series,
    ) {
        $this->coefficients = $coefficients;
    }

    public static function fit(TimeSeries $series, int $order): self
    {
        if ($order < 1) {
            throw new InvalidArgumentException('AR order must be at least 1.');
        }

        if ($series->count() <= $order) {
            throw new InvalidArgumentException('AR fitting requires more observations than the model order.');
        }

        $values = $series->values();
        $rows = [];
        $rhs = [];

        for ($index = $order; $index < $series->count(); $index++) {
            $row = [Number::of(1)];
            for ($lag = 1; $lag <= $order; $lag++) {
                $row[] = $values[$index - $lag];
            }
            $rows[] = $row;
            $rhs[] = $values[$index];
        }

        $xtx = [];
        $columnCount = count($rows[0]);
        for ($row = 0; $row < $columnCount; $row++) {
            $current = [];
            for ($column = 0; $column < $columnCount; $column++) {
                $sum = Number::of(0);
                foreach ($rows as $r) {
                    $sum = $sum->add($r[$row]->mul($r[$column]));
                }
                $current[] = $sum;
            }
            $xtx[] = $current;
        }

        $xty = [];
        for ($column = 0; $column < $columnCount; $column++) {
            $sum = Number::of(0);
            foreach ($rows as $index => $row) {
                $sum = $sum->add($row[$column]->mul($rhs[$index]));
            }
            $xty[] = $sum;
        }

        $solution = LinearSystem::of(
            Matrix::of($xtx),
            Vector::of(...$xty)
        )->solve();

        $coefficients = [];
        $intercept = Number::of(0);
        if ($solution->hasSolution()) {
            $vector = $solution->vector()->values();
            $intercept = $vector[0];
            for ($lag = 1; $lag < count($vector); $lag++) {
                $coefficients[] = $vector[$lag];
            }
        } else {
            foreach (range(1, $order) as $lag) {
                $coefficients[] = Number::of(0);
            }
        }

        return new self($order, $coefficients, $intercept, $series);
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

    public function intercept(): Number
    {
        return $this->intercept;
    }

    public function predict(int $horizon): TimeSeries
    {
        if ($horizon < 1) {
            throw new InvalidArgumentException('Prediction horizon must be at least 1.');
        }

        $history = $this->series->values();
        for ($step = 0; $step < $horizon; $step++) {
            $prediction = $this->intercept;
            for ($lag = 1; $lag <= $this->order; $lag++) {
                $index = count($history) - $lag;
                if ($index >= 0) {
                    $prediction = $prediction->add($this->coefficients[$lag - 1]->mul($history[$index]));
                }
            }
            $history[] = $prediction;
        }

        return TimeSeries::of($history);
    }

    public function residuals(): TimeSeries
    {
        $residuals = [];
        $values = $this->series->values();

        for ($index = $this->order; $index < count($values); $index++) {
            $value = $values[$index];
            $fitted = $this->intercept;
            for ($lag = 1; $lag <= $this->order; $lag++) {
                $fitted = $fitted->add($this->coefficients[$lag - 1]->mul($values[$index - $lag]));
            }
            $residuals[] = $value->sub($fitted);
        }

        return TimeSeries::of($residuals);
    }
}
