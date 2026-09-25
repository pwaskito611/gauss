<?php

declare(strict_types=1);

namespace Gauss\Distribution;

use Gauss\Number\Number;
use Gauss\Probability\Probability;
use InvalidArgumentException;

final class Normal implements ContinuousDistribution
{
    private function __construct(
        private readonly Number $mean,
        private readonly Number $standardDeviation,
    ) {
        if ($this->standardDeviation->compare(0) <= 0) {
            throw new InvalidArgumentException(
                'Normal distribution standard deviation must be positive.'
            );
        }
    }

    public static function of(
        int|float|string|Number $mean,
        int|float|string|Number $standardDeviation
    ): self {
        return new self(
            Number::of($mean),
            Number::of($standardDeviation)
        );
    }

    public function pdf(int|float|string|Number $x): Number
    {
        $value = Number::of($x);

        $z = $value
            ->sub($this->mean)
            ->div($this->standardDeviation);

        $twoPi = Number::of(2)->mul(Number::pi());

        $normalizer = $this->standardDeviation
            ->mul($twoPi->sqrt());

        $exponent = Number::of(-1)
            ->mul($z->pow(2))
            ->div(2)
            ->exp();

        return Number::of(1)
            ->div($normalizer)
            ->mul($exponent);
    }

    public function cdf(int|float|string|Number $x): Probability
    {
        $value = Number::of($x);

        $z = $value
            ->sub($this->mean)
            ->div($this->standardDeviation);

        $sqrtTwo = Number::of(2)->sqrt();

        $erf = $this->erfApprox(
            $z->div($sqrtTwo)
        );

        $cdf = Number::of(1)
            ->add($erf)
            ->div(2);

        return Probability::of($cdf);
    }

    public function expectation(): Number
    {
        return $this->mean;
    }

    public function variance(): Number
    {
        return $this->standardDeviation->pow(2);
    }

    private function erfApprox(Number $x): Number
    {
        if ($x->compare(0) === 0) {
            return Number::of(0);
        }

        $sign = $x->compare(0) < 0
            ? Number::of(-1)
            : Number::of(1);

        $abs = $x->abs();

        $a1 = Number::of('0.254829592');
        $a2 = Number::of('-0.284496736');
        $a3 = Number::of('1.421413741');
        $a4 = Number::of('-1.453152027');
        $a5 = Number::of('1.061405429');

        $t = Number::of(1)->div(
            Number::of(1)->add(
                Number::of('0.3275911')->mul($abs)
            )
        );

        $polynomial = $a5
            ->mul($t)
            ->add($a4)
            ->mul($t)
            ->add($a3)
            ->mul($t)
            ->add($a2)
            ->mul($t)
            ->add($a1)
            ->mul($t);

        $exponential = Number::of(-1)
            ->mul($abs->pow(2))
            ->exp();

        $y = Number::of(1)->sub(
            $polynomial->mul($exponential)
        );

        return $sign->mul($y);
    }
}