<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Gauss\Linear\Matrix;
use Gauss\Linear\Vector;
use Gauss\Number\Number;
use Gauss\Optimization\OneDimensional\GoldenSectionSearch;
use Gauss\Statistics\Statistics;
use Gauss\TimeSeries\Model\AR;
use Gauss\TimeSeries\TimeSeries;
use Gauss\Distribution\Normal;

/**
 * @param callable(): Number $operation
 * @return array{nanoseconds: int, result: Number}
 */
function measure(callable $operation, int $iterations): array
{
    $start = hrtime(true);
    $result = Number::of(0);

    for ($iteration = 0; $iteration < $iterations; $iteration++) {
        $result = $operation();
    }

    return [
        'nanoseconds' => hrtime(true) - $start,
        'result' => $result,
    ];
}

function exactDifference(Number $floatResult, Number $decimalResult): string
{
    return Number::of($floatResult->value())
        ->withBackend(false)
        ->sub($decimalResult)
        ->abs()
        ->round(18)
        ->value();
}

$seriesValues = array_map(
    static fn (int $index): Number => Number::of($index % 13)->div(7),
    range(0, 79),
);
$series = TimeSeries::of($seriesValues);
$model = AR::fit($series, 2);
$floatModel = AR::fit($series, 2, false);

$matrix = Matrix::of([
    ['1.1', '0.2', '0.3'],
    ['0.4', '1.2', '0.6'],
    ['0.7', '0.8', '1.3'],
]);
$floatMatrix = $matrix->offPrecision();
$data = Vector::of(...$seriesValues);
$normal = Normal::of('0.25', '1.5');
$floatNormal = Normal::of('0.25', '1.5', false);

$workloads = [
    'arithmetic-chain' => [
        100,
        static function (): Number {
            $value = Number::of('0.1');
            for ($index = 0; $index < 120; $index++) {
                $value = $value->add('0.003')->mul('1.0001')->div('1.0001');
            }
            return $value;
        },
        static function (): Number {
            $value = Number::of('0.1')->offPrecision();
            for ($index = 0; $index < 120; $index++) {
                $value = $value->add('0.003')->mul('1.0001')->div('1.0001');
            }
            return $value;
        },
    ],
    'matrix-multiply' => [
        30,
        static fn (): Number => $matrix->multiply($matrix)->get(0, 0),
        static fn (): Number => $floatMatrix->multiply($floatMatrix)->get(0, 0),
    ],
    'statistics-variance' => [
        100,
        static fn (): Number => Statistics::populationVariance($data),
        static fn (): Number => Statistics::populationVariance($data, false),
    ],
    'normal-cdf' => [
        20,
        static fn (): Number => $normal->cdf('0.75')->value(),
        static fn (): Number => $floatNormal->cdf('0.75')->value(),
    ],
    'time-series-forecast' => [
        40,
        static fn (): Number => $model->predict(3)->last()->value(),
        static fn (): Number => $floatModel->predict(3)->last()->value(),
    ],
    'golden-section-search' => [
        2,
        static fn (): Number => GoldenSectionSearch::minimize(
            static fn (Number $x): Number => $x->sub(2)->pow(2),
            -5,
            8,
            '0.000001',
            100,
        )->point(),
        static fn (): Number => GoldenSectionSearch::minimize(
            static fn (Number $x): Number => $x->sub(2)->pow(2),
            -5,
            8,
            '0.000001',
            100,
            false,
        )->point(),
    ],
];

$report = [];
foreach ($workloads as $name => [$iterations, $decimalOperation, $floatOperation]) {
    $decimal = measure($decimalOperation, $iterations);
    $float = measure($floatOperation, $iterations);
    $report[$name] = [
        'iterations' => $iterations,
        'bcmath_ms' => round($decimal['nanoseconds'] / 1_000_000, 3),
        'float_ms' => round($float['nanoseconds'] / 1_000_000, 3),
        'float_over_bcmath_ratio' => round($float['nanoseconds'] / $decimal['nanoseconds'], 3),
        'absolute_result_difference' => exactDifference($float['result'], $decimal['result']),
        'bcmath_result' => $decimal['result']->round(18)->value(),
        'float_result' => $float['result']->round(18)->value(),
    ];
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), PHP_EOL;
