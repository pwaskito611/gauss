<?php

declare(strict_types=1);

namespace Gauss\Distribution;

use Gauss\Number\Number;
use Gauss\Probability\Probability;
use InvalidArgumentException;

/**
 * Poisson distribution with positive rate lambda.
 *
 * If X ~ Poisson(lambda), then X takes values in {0, 1, 2, ...} and the PMF is
 * P(X = k) = e^{-lambda} lambda^k / k!.
 */
final class Poisson implements DiscreteDistribution
{
    use DiscreteIndexSupport;

    private function __construct(
        private readonly Number $rate,
    ) {
        if ($this->rate->compare(0) <= 0) {
            throw new InvalidArgumentException('Poisson rate must be positive.');
        }
    }

    /**
     * Creates a Poisson distribution with positive rate lambda.
     *
     * @param int|float|string|Number $rate Rate parameter lambda; valid values satisfy lambda > 0.
     *
     * @throws InvalidArgumentException If rate <= 0.
     */
    public static function of(int|float|string|Number $rate): self
    {
        return new self(Number::of($rate));
    }

    /**
     * Returns the probability mass at x.
     *
     * Inputs that are not non-negative integers return 0.
     */
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

    /**
     * Returns the cumulative probability P(X <= x).
     *
     * Negative x return 0; for x >= 0 the implementation sums terms from k = 0 to floor(x).
     */
    public function cdf(int|float|string|Number $x): Probability
    {
        $threshold = Number::of($x);

        if ($threshold->compare(0) < 0) {
            return Probability::of(0);
        }

        $limit = self::floorIndex($threshold);
        if ($limit < 0) {
            return Probability::of(0);
        }

        $term = $this->rate->mul(Number::of(-1))->exp();
        $sum = $term;
        for ($k = 0; $k < $limit; $k++) {
            $term = $term->mul($this->rate)->div(Number::of($k + 1));
            $sum = $sum->add($term);
        }

        return Probability::of($sum);
    }

    /**
     * Returns the expectation E[X] = lambda.
     */
    public function expectation(): Number
    {
        return $this->rate;
    }

    /**
     * Returns the variance Var(X) = lambda.
     */
    public function variance(): Number
    {
        return $this->rate;
    }

    /**
     * Computes n! for non-negative integer n.
     */
    private function factorial(int $n): Number
    {
        $result = Number::of(1);

        for ($i = 2; $i <= $n; $i++) {
            $result = $result->mul(Number::of($i));
        }

        return $result;
    }
}