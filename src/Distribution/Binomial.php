<?php

declare(strict_types=1);

namespace Gauss\Distribution;

use Gauss\Number\Number;
use Gauss\Probability\Probability;
use InvalidArgumentException;

final class Binomial implements DiscreteDistribution
{
    private function __construct(
        private readonly int $trials,
        private readonly Number $probability,
    ) {
        if ($this->trials < 0) {
            throw new InvalidArgumentException('Binomial trials must be non-negative.');
        }

        if ($this->probability->compare(0) < 0 || $this->probability->compare(1) > 0) {
            throw new InvalidArgumentException('Binomial probability must satisfy 0 <= p <= 1.');
        }
    }

    public static function of(int $trials, int|float|string|Number $probability): self
    {
        return new self($trials, Number::of($probability));
    }

    public function pmf(int|float|string|Number $x): Probability
    {
        $k = (int) Number::of($x)->value();
        if ($k < 0 || $k > $this->trials) {
            return Probability::of(0);
        }

        $combination = Number::of(1);
        for ($i = 1; $i <= $k; $i++) {
            $combination = $combination->mul(Number::of($this->trials - $k + $i))->div(Number::of($i));
        }

        $success = $this->probability->pow($k);
        $failure = Number::of(1)->sub($this->probability)->pow($this->trials - $k);

        return Probability::of($combination->mul($success)->mul($failure));
    }

    public function cdf(int|float|string|Number $x): Probability
    {
        $threshold = (int) Number::of($x)->value();
        if ($threshold < 0) {
            return Probability::of(0);
        }
        if ($threshold >= $this->trials) {
            return Probability::of(1);
        }

        $sum = Number::of(0);
        for ($k = 0; $k <= $threshold; $k++) {
            $sum = $sum->add($this->pmf($k)->value());
        }

        return Probability::of($sum);
    }

    public function expectation(): Number
    {
        return Number::of($this->trials)->mul($this->probability);
    }

    public function variance(): Number
    {
        return Number::of($this->trials)
            ->mul($this->probability)
            ->mul(Number::of(1)->sub($this->probability));
    }
}
