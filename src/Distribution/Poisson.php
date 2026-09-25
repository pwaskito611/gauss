<?php

declare(strict_types=1);

namespace Gauss\Distribution;

use Gauss\Number\Number;
use Gauss\Probability\Probability;
use InvalidArgumentException;

final class Poisson implements DiscreteDistribution
{
    private function __construct(
        private readonly Number $rate,
    ) {
        if ($this->rate->compare(0) <= 0) {
            throw new InvalidArgumentException('Poisson rate must be positive.');
        }
    }

    public static function of(int|float|string|Number $rate): self
    {
        return new self(Number::of($rate));
    }

    public function pmf(int|float|string|Number $x): Probability
    {
        $k = Number::of($x);

        if ($k->compare(0) < 0) {
            return Probability::of(0);
        }
        if (! $k->isIntegerLike()) {
            return Probability::of(0);
        }

        $factorial = $this->factorial((int) $k->value());

        $value = $this->rate
            ->pow((int) $k->value())
            ->mul(
                $this->rate
                    ->mul(Number::of(-1))
                    ->exp()
            )
            ->div($factorial);

        return Probability::of($value);
    }

    public function cdf(int|float|string|Number $x): Probability
    {
        $threshold = Number::of($x);

        if ($threshold->compare(0) < 0) {
            return Probability::of(0);
        }

        $sum = Number::of(0);

        for ($k = 0; $k <= (int) $threshold->value(); $k++) {
            $sum = $sum->add($this->pmf($k)->value());
        }

        return Probability::of($sum);
    }

    public function expectation(): Number
    {
        return $this->rate;
    }

    public function variance(): Number
    {
        return $this->rate;
    }

    private function factorial(int $n): Number
    {
        $result = Number::of(1);

        for ($i = 2; $i <= $n; $i++) {
            $result = $result->mul(Number::of($i));
        }

        return $result;
    }
}