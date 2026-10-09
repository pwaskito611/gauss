<?php

declare(strict_types=1);

namespace Gauss\Distribution;

use Gauss\Number\Number;
use Gauss\Probability\Probability;
use InvalidArgumentException;

/**
 * Exponential distribution with rate parameter lambda > 0.
 *
 * This models the waiting time until an event occurs. The support is x >= 0 and
 * the density is f(x) = lambda * exp(-lambda * x) for x >= 0.
 */
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

    /**
     * Creates an exponential distribution with rate lambda.
     *
     * @param int|float|string|Number $rate Event rate. Valid values satisfy lambda > 0.
     *
     * @throws InvalidArgumentException If rate <= 0.
     */
    public static function of(int|float|string|Number $rate, bool $precision = true): self
    {
        return new self(Number::of($rate)->withBackend(! $precision));
    }

    public function offPrecision(): self
    {
        return new self($this->rate->offPrecision());
    }

    /**
     * Returns the probability density at x.
     *
     * For x < 0 the implementation returns 0. For x >= 0 it returns lambda * e^{-lambda * x}.
     */
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

    /**
     * Returns the cumulative probability P(X <= x).
     *
     * For x < 0 the implementation returns 0; otherwise it returns 1 - e^{-lambda * x}.
     */
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

    /**
     * Returns the expectation E[X] = 1 / lambda.
     */
    public function expectation(): Number
    {
        return Number::of(1)->div($this->rate);
    }

    /**
     * Returns the variance Var(X) = 1 / lambda^2.
     */
    public function variance(): Number
    {
        return Number::of(1)->div($this->rate->pow(2));
    }
}