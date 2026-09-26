<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\TimeSeries\Model;

use Gauss\Number\Number;
use Gauss\TimeSeries\Model\AR;
use Gauss\TimeSeries\Model\ARMA;
use Gauss\TimeSeries\Model\MA;
use Gauss\TimeSeries\TimeSeries;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ARMATest extends TestCase
{
    public function testArmaOneOneFitsAndPredicts(): void
    {
        $series = TimeSeries::of([1, 2, 3, 4, 5, 6, 7, 8]);
        $model = ARMA::fit($series, 1, 1);

        self::assertSame(1, $model->arOrder());
        self::assertSame(1, $model->maOrder());
        self::assertNotSame('0.5', $model->arCoefficients()[0]->value());
        self::assertNotSame('0.25', $model->maCoefficients()[0]->value());
        self::assertTrue($model->predict(2)->count() >= 2);
        self::assertNotEmpty($model->residuals()->observations());
    }

    public function testArmaRejectsInvalidOrders(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ARMA::fit(TimeSeries::of([1, 2, 3]), 0, 0);
    }

    public function testArmaJointFitRecoversSyntheticArmaOneOne(): void
    {
        $intercept = Number::of(2);
        $arCoefficient = Number::of('0.6');
        $maCoefficient = Number::of('0.35');
        $values = [Number::of(0)];
        $innovations = [];
        $seed = 17;

        for ($index = 0; $index < 120; $index++) {
            $seed = ($seed * 73 + 41) % 997;
            $innovation = Number::of($seed - 498)->div(1000);
            $innovations[] = $innovation;
            $previousValue = $values[count($values) - 1];
            $previousInnovation = $index === 0 ? Number::of(0) : $innovations[$index - 1];
            $values[] = $intercept
                ->add($arCoefficient->mul($previousValue))
                ->add($maCoefficient->mul($previousInnovation))
                ->add($innovation)
                ->round(6);
        }

        array_shift($values);
        $series = TimeSeries::of($values);
        $model = ARMA::fit($series, 1, 1);
        $independentAr = AR::fit($series, 1);
        $independentMa = MA::fit($series, 1);

        self::assertLessThanOrEqual(0, $model->arCoefficients()[0]->sub($arCoefficient)->abs()->compare('0.12'));
        self::assertLessThanOrEqual(0, $model->maCoefficients()[0]->sub($maCoefficient)->abs()->compare('0.2'));
        self::assertLessThanOrEqual(0, $model->intercept()->sub($intercept)->abs()->compare('0.35'));
        self::assertNotSame(0, $model->intercept()->compare($independentAr->intercept()));
        self::assertNotSame(0, $model->maCoefficients()[0]->compare($independentMa->coefficients()[0]));

        $residuals = $model->residuals();
        self::assertSame(119, $residuals->count());
        self::assertLessThanOrEqual(0, $residuals->variance()->compare('0.5'));
    }

    public function testArmaResidualsStartAfterFullArHistoryAndUseMaRecursion(): void
    {
        $series = TimeSeries::of([1, 4, 2, 8, 3, 9, 0, 7, 2, 5]);
        $model = ARMA::fit($series, 2, 1);
        $allResiduals = [];
        $values = $series->values();

        foreach ($values as $index => $value) {
            $fitted = $model->intercept();
            for ($lag = 1; $lag <= $model->arOrder(); $lag++) {
                if ($index >= $lag) {
                    $fitted = $fitted->add($model->arCoefficients()[$lag - 1]->mul($values[$index - $lag]));
                }
            }
            for ($lag = 1; $lag <= $model->maOrder(); $lag++) {
                if ($index >= $lag) {
                    $fitted = $fitted->add($model->maCoefficients()[$lag - 1]->mul($allResiduals[$index - $lag]));
                }
            }
            $allResiduals[] = $value->sub($fitted);
        }

        $residuals = $model->residuals();
        self::assertSame($series->count() - $model->arOrder(), $residuals->count());
        self::assertLessThanOrEqual(
            0,
            $residuals->valueAt(0)
                ->sub($allResiduals[$model->arOrder()])
                ->abs()
                ->compare('0.00000000000000000001'),
        );

        $expectedPrediction = $model->intercept()
            ->add($model->arCoefficients()[0]->mul($series->last()->value()))
            ->add($model->arCoefficients()[1]->mul($series->valueAt($series->count() - 2)))
            ->add($model->maCoefficients()[0]->mul($residuals->last()->value()));
        self::assertLessThanOrEqual(
            0,
            $model->predict(1)->last()->value()->sub($expectedPrediction)->abs()->compare('0.00000000000000000001'),
        );
    }

    public function testArmaResidualWindowsStartAfterMaximumModelOrder(): void
    {
        $series = TimeSeries::of([
            1, 4, 2, 8, 3, 9, 0, 7, 2, 5,
            6, 1, 8, 4, 0, 9, 3, 7, 5, 2,
        ]);

        foreach ([
            [1, 3, 17],
            [3, 1, 17],
            [2, 2, 18],
        ] as [$arOrder, $maOrder, $expectedResidualCount]) {
            $model = ARMA::fit($series, $arOrder, $maOrder);

            self::assertSame($expectedResidualCount, $model->residuals()->count());
        }
    }

    public function testArmaPredictUsesAvailableResidualHistoryWhenMaOrderExceedsArOrder(): void
    {
        $series = TimeSeries::of([
            1, 4, 2, 8, 3, 9, 0, 7, 2, 5,
            6, 1, 8, 4, 0, 9, 3, 7, 5, 2,
        ]);
        $model = ARMA::fit($series, 1, 3);
        $residuals = $model->residuals()->values();
        $expectedNextValue = $model->intercept()
            ->add($model->arCoefficients()[0]->mul($series->last()->value()));

        for ($lag = 1; $lag <= $model->maOrder(); $lag++) {
            $expectedNextValue = $expectedNextValue->add(
                $model->maCoefficients()[$lag - 1]->mul($residuals[count($residuals) - $lag])
            );
        }

        $prediction = $model->predict(2);

        self::assertSame(22, $prediction->count());
        self::assertSame(0, $prediction->valueAt(20)->compare($expectedNextValue));
        self::assertNotNull($prediction->valueAt(21));
    }
}
