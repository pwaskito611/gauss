<?php

declare(strict_types=1);

namespace Gauss\Distribution;

use Gauss\Number\Number;
use Gauss\Probability\Probability;
use InvalidArgumentException;

final class Geometric implements DiscreteDistribution
{
    private function __construct(
        private readonly Number $probability,
    ) {
        if ($this->probability->compare(0) <= 0 || $this->probability->compare(1) > 0) {
            throw new InvalidArgumentException('Geometric probability must satisfy 0 < p <= 1.');
        }
    }

    public static function of(int|float|string|Number $probability): self
    {
        return new self(Number::of($probability));
    }

    public function pmf(int|float|string|Number $x): Probability
    {
        $k = Number::of($x);
        if ($k->compare(1) < 0) {
            return Probability::of(0);
        }

        $failure = Number::of(1)->sub($this->probability);
        $power = $failure->pow((int) $k->value() - 1);
        $result = $power->mul($this->probability);

        return Probability::of($result);
    }

    public function cdf(int|float|string|Number $x): Probability
    {
        $k = Number::of($x);
        if ($k->compare(0) <= 0) {
            return Probability::of(0);
        }

        $sum = Number::of(0);
        for ($i = 1; $i <= (int) $k->value(); $i++) {
            $sum = $sum->add($this->pmf($i)->value());
        }

        return Probability::of($sum);
    }

    public function expectation(): Number
    {
        return Number::of(1)->div($this->probability);
    }

    public function variance(): Number
    {
        $oneMinus = Number::of(1)->sub($this->probability);

        return $oneMinus->div($this->probability->pow(2));
    }
}
