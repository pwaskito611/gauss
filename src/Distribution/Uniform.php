<?php

declare(strict_types=1);

namespace Gauss\Distribution;

use Gauss\Number\Number;
use Gauss\Probability\Probability;
use InvalidArgumentException;

final class Uniform implements ContinuousDistribution
{
    private function __construct(
        private readonly Number $a,
        private readonly Number $b,
    ) {
        if ($this->b->compare($this->a) <= 0) {
            throw new InvalidArgumentException('Uniform distribution requires a < b.');
        }
    }

    public static function of(int|float|string|Number $a, int|float|string|Number $b): self
    {
        return new self(Number::of($a), Number::of($b));
    }

    public function pdf(int|float|string|Number $x): Number
    {
        $value = Number::of($x);
        if ($value->compare($this->a) < 0 || $value->compare($this->b) > 0) {
            return Number::of(0);
        }

        return Number::of(1)->div($this->b->sub($this->a));
    }

    public function cdf(int|float|string|Number $x): Probability
    {
        $value = Number::of($x);
        if ($value->compare($this->a) <= 0) {
            return Probability::of(0);
        }
        if ($value->compare($this->b) >= 0) {
            return Probability::of(1);
        }

        return Probability::of(
            $value->sub($this->a)->div($this->b->sub($this->a))
        );
    }

    public function expectation(): Number
    {
        return $this->a->add($this->b)->div(2);
    }

    public function variance(): Number
    {
        $span = $this->b->sub($this->a);

        return $span->pow(2)->div(12);
    }
}
