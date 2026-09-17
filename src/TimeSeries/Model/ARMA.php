<?php

declare(strict_types=1);

namespace Gauss\TimeSeries\Model;

use Gauss\Number\Number;
use Gauss\TimeSeries\TimeSeries;
use InvalidArgumentException;

final class ARMA
{
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

        if ($series->count() <= max($arOrder, $maOrder)) {
            throw new InvalidArgumentException('ARMA fitting requires more observations than the maximum model order.');
        }

        $arModel = AR::fit($series, $arOrder);
        $maModel = MA::fit($series, $maOrder);

        return new self(
            $arOrder,
            $maOrder,
            $arModel->coefficients(),
            $maModel->coefficients(),
            $arModel->intercept(),
            $series,
        );
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
            $history[] = $prediction;
            $residuals[] = Number::of(0);
        }

        return TimeSeries::of($history);
    }

    public function residuals(): TimeSeries
    {
        $values = $this->series->values();
        $residuals = [];

        foreach ($values as $index => $value) {
            $fitted = $this->intercept;
            for ($lag = 1; $lag <= $this->arOrder; $lag++) {
                if ($index - $lag >= 0) {
                    $fitted = $fitted->add($this->arCoefficients[$lag - 1]->mul($values[$index - $lag]));
                }
            }
            for ($lag = 1; $lag <= $this->maOrder; $lag++) {
                if ($index - $lag >= 0) {
                    $fitted = $fitted->add($this->maCoefficients[$lag - 1]->mul($residuals[$index - $lag]));
                }
            }
            $residuals[] = $value->sub($fitted);
        }

        return TimeSeries::of($residuals);
    }
}
