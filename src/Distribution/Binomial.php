<?php

declare(strict_types=1);

namespace Gauss\Distribution;

use Gauss\Number\Number;
use Gauss\Probability\Probability;
use InvalidArgumentException;

final class Binomial implements DiscreteDistribution
{
    use DiscreteIndexSupport;

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

    public static function of(
        int $trials,
        int|float|string|Number $probability,
        bool $precision = true,
    ): self
    {
        return new self($trials, Number::of($probability)->withBackend(! $precision));
    }

    public function offPrecision(): self
    {
        return new self($this->trials, $this->probability->offPrecision());
    }

    public function pmf(int|float|string|Number $x): Probability
    {
        $value = Number::of($x);
        if (! $value->isIntegerLike()) {
            return Probability::of(0);
        }

        $k = (int) $value->value();
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
        $value = Number::of($x);
        if ($value->compare(0) < 0) {
            return Probability::of(0);
        }

        $threshold = self::floorIndex($value);
        if ($threshold >= $this->trials) {
            return Probability::of(1);
        }

        if ($this->probability->compare(0) === 0) {
            return Probability::of($threshold >= 0 ? 1 : 0);
        }

        if ($this->probability->compare(1) === 0) {
            return Probability::of($threshold >= $this->trials ? 1 : 0);
        }

        $term = $this->pmf(0)->value();
        $sum = $term;
        for ($k = 0; $k < $threshold; $k++) {
            $term = $term
                ->mul(Number::of($this->trials - $k))
                ->div(Number::of($k + 1))
                ->mul($this->probability)
                ->div(Number::of(1)->sub($this->probability));
            $sum = $sum->add($term);
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
