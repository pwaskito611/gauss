<?php

declare(strict_types=1);

namespace Gauss\TimeSeries\Model;

use Gauss\Linear\LinearSystem;
use Gauss\Linear\Matrix;
use Gauss\Linear\Solution\UniqueSolution;
use Gauss\Linear\Vector;
use Gauss\Number\Number;
use Gauss\Statistics\Statistics;
use Gauss\TimeSeries\TimeSeries;
use InvalidArgumentException;

/**
 * Fits an ARMA(p, q) model without automatic stationarity or invertibility checks.
 * The model remains a primitive fitting object; statistical assumptions are left to the caller.
 */
final class ARMA
{
    /**
     * ARMA uses a separate numerical scale from MA because the joint parameter iteration
     * has different convergence and residual precision needs.
     */
    private const WORK_SCALE = 20;

    private ?TimeSeries $cachedResiduals = null;

    /** @var list<Number> */
    private readonly array $arCoefficients;

    /** @var list<Number> */
    private readonly array $maCoefficients;

    /**
     * @param list<Number> $arCoefficients
     * @param list<Number> $maCoefficients
     */
    private function __construct(
        private readonly int $arOrder,
        private readonly int $maOrder,
        array $arCoefficients,
        array $maCoefficients,
        private readonly Number $intercept,
        private readonly TimeSeries $series,
    ) {
        $this->arCoefficients = $arCoefficients;
        $this->maCoefficients = $maCoefficients;
    }

    public static function fit(TimeSeries $series, int $arOrder, int $maOrder): self
    {
        if ($arOrder < 1 || $maOrder < 1) {
            throw new InvalidArgumentException('ARMA orders must each be at least 1.');
        }

        $sampleCount = $series->count();
        if ($sampleCount <= max($arOrder, $maOrder)) {
            throw new InvalidArgumentException('ARMA fitting requires more observations than the maximum model order.');
        }

        if ($sampleCount - max($arOrder, $maOrder) < 1 + $arOrder + $maOrder) {
            throw new InvalidArgumentException('ARMA fitting requires enough observations to estimate all model parameters.');
        }

        $initialOrder = min(
            max($arOrder + $maOrder + 1, intdiv($sampleCount, 30)),
            intdiv($sampleCount - 1, 2),
        );
        $values = $series->values();
        $initialResiduals = self::initialResiduals($values, $initialOrder);
        $intercept = Statistics::mean($values);
        $arCoefficients = array_fill(0, $arOrder, Number::of(0));
        $maCoefficients = array_fill(0, $maOrder, Number::of(0));
        $residuals = $initialResiduals;
        $initialStart = $initialOrder + $maOrder;
        $refinementStart = max($arOrder, $maOrder);

        for ($iteration = 0; $iteration < 8; $iteration++) {
            $start = $iteration === 0 ? $initialStart : $refinementStart;
            $parameters = self::estimateJointParameters(
                $values,
                $residuals,
                $arOrder,
                $maOrder,
                $start,
            );

            if ($parameters === null) {
                if ($iteration === 0 && $start !== $refinementStart) {
                    $residuals = $initialResiduals;
                    continue;
                }

                break;
            }

            $nextIntercept = $parameters[0];
            $nextArCoefficients = array_slice($parameters, 1, $arOrder);
            $nextMaCoefficients = array_slice($parameters, 1 + $arOrder, $maOrder);
            $converged = self::parametersConverged(
                [$intercept, ...$arCoefficients, ...$maCoefficients],
                [$nextIntercept, ...$nextArCoefficients, ...$nextMaCoefficients],
            );

            $intercept = $nextIntercept;
            $arCoefficients = $nextArCoefficients;
            $maCoefficients = $nextMaCoefficients;
            $residuals = self::calculateResiduals(
                $values,
                $intercept,
                $arCoefficients,
                $maCoefficients,
            );

            if ($converged) {
                break;
            }
        }

        $model = new self(
            $arOrder,
            $maOrder,
            $arCoefficients,
            $maCoefficients,
            $intercept,
            $series,
        );
        $model->cachedResiduals = $model->computeResiduals();

        return $model;
    }

    public function arOrder(): int
    {
        return $this->arOrder;
    }

    public function maOrder(): int
    {
        return $this->maOrder;
    }

    /** @return list<Number> */
    public function arCoefficients(): array
    {
        return $this->arCoefficients;
    }

    /** @return list<Number> */
    public function maCoefficients(): array
    {
        return $this->maCoefficients;
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
        $residuals = $this->residuals()->values();
        $forecast = [];

        for ($step = 0; $step < $horizon; $step++) {
            $prediction = $this->intercept;
            for ($lag = 1; $lag <= $this->arOrder; $lag++) {
                $index = count($history) - $lag;
                if ($index >= 0) {
                    $prediction = $prediction->add($this->arCoefficients[$lag - 1]->mul($history[$index]));
                }
            }
            for ($lag = 1; $lag <= $this->maOrder; $lag++) {
                $index = count($residuals) - $lag;
                if ($index >= 0) {
                    $prediction = $prediction->add($this->maCoefficients[$lag - 1]->mul($residuals[$index]));
                }
            }
            $forecast[] = $prediction;
            $history[] = $prediction;
            $residuals[] = Number::of(0);
        }

        return TimeSeries::of($forecast);
    }

    public function residuals(): TimeSeries
    {
        if ($this->cachedResiduals === null) {
            $this->cachedResiduals = $this->computeResiduals();
        }

        return $this->cachedResiduals;
    }

    private function computeResiduals(): TimeSeries
    {
        $values = $this->series->values();
        $residuals = self::calculateResiduals(
            $values,
            $this->intercept,
            $this->arCoefficients,
            $this->maCoefficients,
        );

        $startIndex = max($this->arOrder, $this->maOrder);

        return TimeSeries::of(array_slice($residuals, $startIndex));
    }

    /**
     * @param list<Number> $values
     * @param list<Number> $residuals
     * @return list<Number>|null
     */
    private static function estimateJointParameters(
        array $values,
        array $residuals,
        int $arOrder,
        int $maOrder,
        int $start,
    ): ?array {
        $rows = [];
        $targets = [];

        for ($index = $start; $index < count($values); $index++) {
            $row = [Number::of(1)];
            for ($lag = 1; $lag <= $arOrder; $lag++) {
                $row[] = $values[$index - $lag];
            }
            for ($lag = 1; $lag <= $maOrder; $lag++) {
                $row[] = $residuals[$index - $lag] ?? Number::of(0);
            }
            $rows[] = $row;
            $targets[] = $values[$index];
        }

        if ($rows === [] || count($rows) < count($rows[0])) {
            return null;
        }

        $columnCount = count($rows[0]);
        $xtx = [];
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

        $solution = LinearSystem::of(Matrix::of($xtx), Vector::of(...$xty))->solve();

        return $solution instanceof UniqueSolution
            ? array_map(self::limitPrecision(...), $solution->vector()->values())
            : null;
    }

    /**
     * @param list<Number> $values
     * @return list<Number>
     */
    private static function initialResiduals(array $values, int $maximumOrder): array
    {
        for ($order = $maximumOrder; $order >= 1; $order--) {
            $rows = [];
            $targets = [];
            for ($index = $order; $index < count($values); $index++) {
                $row = [Number::of(1)];
                for ($lag = 1; $lag <= $order; $lag++) {
                    $row[] = $values[$index - $lag];
                }
                $rows[] = $row;
                $targets[] = $values[$index];
            }

            $parameters = self::solveRegression($rows, $targets);
            if ($parameters === null) {
                continue;
            }

            $residuals = array_fill(0, count($values), Number::of(0));
            for ($index = $order; $index < count($values); $index++) {
                $fitted = $parameters[0];
                for ($lag = 1; $lag <= $order; $lag++) {
                    $fitted = $fitted->add($parameters[$lag]->mul($values[$index - $lag]));
                }
                $residuals[$index] = self::limitPrecision($values[$index]->sub($fitted));
            }

            return $residuals;
        }

        $mean = Statistics::mean($values);

        return array_map(
            static fn (Number $value): Number => self::limitPrecision($value->sub($mean)),
            $values,
        );
    }

    /**
     * @param list<list<Number>> $rows
     * @param list<Number> $targets
     * @return list<Number>|null
     */
    private static function solveRegression(array $rows, array $targets): ?array
    {
        if ($rows === [] || count($rows) < count($rows[0])) {
            return null;
        }

        $columnCount = count($rows[0]);
        $xtx = [];
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

        $solution = LinearSystem::of(Matrix::of($xtx), Vector::of(...$xty))->solve();

        return $solution instanceof UniqueSolution ? $solution->vector()->values() : null;
    }

    /** @param list<Number> $values
     *  @param list<Number> $arCoefficients
     *  @param list<Number> $maCoefficients
     *  @return list<Number>
     */
    private static function calculateResiduals(
        array $values,
        Number $intercept,
        array $arCoefficients,
        array $maCoefficients,
    ): array {
        $residuals = [];

        foreach ($values as $index => $value) {
            $fitted = $intercept;
            foreach ($arCoefficients as $coefficientIndex => $coefficient) {
                $lag = $coefficientIndex + 1;
                if ($index >= $lag) {
                    $fitted = $fitted->add($coefficient->mul($values[$index - $lag]));
                }
            }
            foreach ($maCoefficients as $coefficientIndex => $coefficient) {
                $lag = $coefficientIndex + 1;
                if ($index >= $lag) {
                    $fitted = $fitted->add($coefficient->mul($residuals[$index - $lag]));
                }
            }
            $residuals[] = self::limitPrecision($value->sub($fitted));
        }

        return $residuals;
    }

    private static function limitPrecision(Number $value): Number
    {
        return $value->round(self::WORK_SCALE);
    }

    /** @param list<Number> $previous
     *  @param list<Number> $next
     */
    private static function parametersConverged(array $previous, array $next): bool
    {
        foreach ($next as $index => $parameter) {
            if ($parameter->sub($previous[$index])->abs()->compare('0.00000001') > 0) {
                return false;
            }
        }

        return true;
    }
}
