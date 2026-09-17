<?php

declare(strict_types=1);

namespace Gauss\Distribution;

use Gauss\Number\Number;
use Gauss\Probability\Probability;
use InvalidArgumentException;

final class Bernoulli implements DiscreteDistribution
{
    private function __construct(
        private readonly Number $probability,
    ) {
        if ($this->probability->compare(0) < 0 || $this->probability->compare(1) > 0) {
            throw new InvalidArgumentException('Bernoulli probability must satisfy 0 <= p <= 1.');
        }
    }

    public static function of(int|float|string|Number $probability): self
    {
        return new self(Number::of($probability));
    }

    public function pmf(int|float|string|Number $x): Probability
    {
        $value = Number::of($x);
        if ($value->compare(0) === 0) {
            return Probability::of(Number::of(1)->sub($this->probability));
        }
        if ($value->compare(1) === 0) {
            return Probability::of($this->probability);
        }

        return Probability::of(0);
    }

    public function cdf(int|float|string|Number $x): Probability
    {
        $value = Number::of($x);
        if ($value->compare(0) < 0) {
            return Probability::of(0);
        }
        if ($value->compare(0) >= 0 && $value->compare(1) < 0) {
            return Probability::of(Number::of(1)->sub($this->probability));
        }

        return Probability::of(1);
    }

    public function expectation(): Number
    {
        return $this->probability;
    }

    public function variance(): Number
    {
        return $this->probability->mul(Number::of(1)->sub($this->probability));
    }
}
