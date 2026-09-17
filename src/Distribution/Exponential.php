<?php

declare(strict_types=1);

namespace Gauss\Distribution;

use Gauss\Number\Number;
use Gauss\Probability\Probability;
use InvalidArgumentException;

final class Exponential implements ContinuousDistribution
{
    private function __construct(
        private readonly Number $rate,
    ) {
        if ($this->rate->compare(0) <= 0) {
            throw new InvalidArgumentException(
                'Exponential rate must be positive.'
            );
        }
    }

    public static function of(int|float|string|Number $rate): self
    {
        return new self(Number::of($rate));
    }

    public function pdf(int|float|string|Number $x): Number
    {
        $value = Number::of($x);

        if ($value->compare(0) < 0) {
            return Number::of(0);
        }

        $exponent = $this->rate
            ->mul($value)
            ->mul(Number::of(-1))
            ->exp();

        return $this->rate->mul($exponent);
    }

    public function cdf(int|float|string|Number $x): Probability
    {
        $value = Number::of($x);

        if ($value->compare(0) < 0) {
            return Probability::of(0);
        }

        $exponent = $this->rate
            ->mul($value)
            ->mul(Number::of(-1))
            ->exp();

        $cdf = Number::of(1)->sub($exponent);

        return Probability::of($cdf);
    }

    public function expectation(): Number
    {
        return Number::of(1)->div($this->rate);
    }

    public function variance(): Number
    {
        return Number::of(1)->div($this->rate->pow(2));
    }
}