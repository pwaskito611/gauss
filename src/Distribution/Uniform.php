<?php

declare(strict_types=1);

namespace Gauss\Distribution;

use Gauss\Number\Number;
use Gauss\Probability\Probability;
use InvalidArgumentException;

/**
 * Continuous uniform distribution over the interval [a, b].
 *
 * This models a random variable equally likely to take any value between a and b,
 * with density 1 / (b - a) on that interval and zero elsewhere.
 */
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

    /**
     * Creates a uniform distribution over the interval [a, b].
     *
     * @param int|float|string|Number $a Lower bound; must satisfy a < b.
     * @param int|float|string|Number $b Upper bound; must satisfy b > a.
     *
     * @throws InvalidArgumentException If a >= b.
     */
    public static function of(
        int|float|string|Number $a,
        int|float|string|Number $b,
        bool $precision = true,
    ): self
    {
        $backend = ! $precision;
        return new self(
            Number::of($a)->withBackend($backend),
            Number::of($b)->withBackend($backend),
        );
    }

    public function offPrecision(): self
    {
        return new self($this->a->offPrecision(), $this->b->offPrecision());
    }

    /**
     * Returns the density at x.
     *
     * The implementation returns 0 for x < a and x > b, and 1 / (b - a) for a <= x <= b.
     */
    public function pdf(int|float|string|Number $x): Number
    {
        $value = Number::of($x);
        if ($value->compare($this->a) < 0 || $value->compare($this->b) > 0) {
            return Number::of(0);
        }

        return Number::of(1)->div($this->b->sub($this->a));
    }

    /**
     * Returns the cumulative probability P(X <= x).
     *
     * For x <= a, the result is 0. For x >= b, the result is 1. For a < x < b,
     * the result is (x - a) / (b - a).
     */
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

    /**
     * Returns the expectation E[X] = (a + b) / 2.
     */
    public function expectation(): Number
    {
        return $this->a->add($this->b)->div(2);
    }

    /**
     * Returns the variance Var(X) = (b - a)^2 / 12.
     */
    public function variance(): Number
    {
        $span = $this->b->sub($this->a);

        return $span->pow(2)->div(12);
    }
}
