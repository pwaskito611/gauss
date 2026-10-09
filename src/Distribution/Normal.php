<?php

declare(strict_types=1);

namespace Gauss\Distribution;

use Gauss\Number\Number;
use Gauss\Probability\Probability;
use InvalidArgumentException;

/**
 * Normal distribution with mean mu and standard deviation sigma > 0.
 *
 * The density is the Gaussian bell curve and the CDF is computed via the
 * Abramowitz-Stegun erf approximation. This is a numerical approximation of the
 * normal CDF, not an exact symbolic evaluation.
 */
final class Normal implements ContinuousDistribution
{
    private const A1 = '0.254829592';
    private const A2 = '-0.284496736';
    private const A3 = '1.421413741';
    private const A4 = '-1.453152027';
    private const A5 = '1.061405429';
    private const P = '0.3275911';

    private function __construct(
        private readonly Number $mean,
        private readonly Number $standardDeviation,
    ) {
        if ($this->standardDeviation->compare(0) <= 0) {
            throw new InvalidArgumentException(
                'Normal distribution standard deviation must be positive.'
            );
        }
    }

    /**
     * Creates a normal distribution with mean mu and positive standard deviation sigma.
     *
     * @param int|float|string|Number $mean Mean value mu.
     * @param int|float|string|Number $standardDeviation Standard deviation sigma; must be > 0.
     *
     * @throws InvalidArgumentException If sigma <= 0.
     */
    public static function of(
        int|float|string|Number $mean,
        int|float|string|Number $standardDeviation,
        bool $precision = true,
    ): self {
        $backend = ! $precision;
        return new self(
            Number::of($mean)->withBackend($backend),
            Number::of($standardDeviation)->withBackend($backend)
        );
    }

    public function offPrecision(): self
    {
        return new self($this->mean->offPrecision(), $this->standardDeviation->offPrecision());
    }

    /**
     * Returns the Gaussian density at x.
     */
    public function pdf(int|float|string|Number $x): Number
    {
        $value = Number::of($x);

        $z = $value
            ->sub($this->mean)
            ->div($this->standardDeviation);

        $floatBackend = $value->usesFloatBackend()
            || $this->mean->usesFloatBackend()
            || $this->standardDeviation->usesFloatBackend();
        $twoPi = Number::of(2)
            ->withBackend($floatBackend)
            ->mul(Number::pi()->withBackend($floatBackend));

        $normalizer = $this->standardDeviation
            ->mul($twoPi->sqrt());

        $exponent = Number::of(-1)
            ->mul($z->pow(2))
            ->div(2)
            ->exp();

        return Number::of(1)
            ->div($normalizer)
            ->mul($exponent);
    }

    /**
     * Returns the cumulative probability P(X <= x).
     *
     * The implementation evaluates the standard normal CDF via the Abramowitz–Stegun
     * 7.1.26 approximation for erf(x).
     */
    public function cdf(int|float|string|Number $x): Probability
    {
        $value = Number::of($x);

        $z = $value
            ->sub($this->mean)
            ->div($this->standardDeviation);

        $sqrtTwo = Number::of(2)
            ->withBackend(
                $value->usesFloatBackend()
                || $this->mean->usesFloatBackend()
                || $this->standardDeviation->usesFloatBackend()
            )
            ->sqrt();

        $erf = $this->erfApprox(
            $z->div($sqrtTwo)
        );

        $cdf = Number::of(1)
            ->add($erf)
            ->div(2);

        return Probability::of($cdf);
    }

    /**
     * Returns the expectation E[X] = mu.
     */
    public function expectation(): Number
    {
        return $this->mean;
    }

    /**
     * Returns the variance Var(X) = sigma^2.
     */
    public function variance(): Number
    {
        return $this->standardDeviation->pow(2);
    }

    /**
     * Abramowitz-Stegun 7.1.26 approximation for erf(x).
     * This remains a numerical approximation; Number precision does not imply
     * exact transcendental values for the normal CDF.
     */
    private function erfApprox(Number $x): Number
    {
        if ($x->compare(0) === 0) {
            return Number::of(0)->withBackend($x->usesFloatBackend());
        }

        $sign = $x->compare(0) < 0
            ? Number::of(-1)
            : Number::of(1);

        $abs = $x->abs();

        $floatBackend = $x->usesFloatBackend();
        $a1 = Number::of(self::A1)->withBackend($floatBackend);
        $a2 = Number::of(self::A2)->withBackend($floatBackend);
        $a3 = Number::of(self::A3)->withBackend($floatBackend);
        $a4 = Number::of(self::A4)->withBackend($floatBackend);
        $a5 = Number::of(self::A5)->withBackend($floatBackend);
        $p = Number::of(self::P)->withBackend($floatBackend);

        $t = Number::of(1)->withBackend($floatBackend)->div(
            Number::of(1)->withBackend($floatBackend)->add(
                $p->mul($abs)
            )
        );

        $polynomial = $a5
            ->mul($t)
            ->add($a4)
            ->mul($t)
            ->add($a3)
            ->mul($t)
            ->add($a2)
            ->mul($t)
            ->add($a1)
            ->mul($t);

        $exponential = Number::of(-1)->withBackend($floatBackend)
            ->mul($abs->pow(2))
            ->exp();

        $y = Number::of(1)->withBackend($floatBackend)->sub(
            $polynomial->mul($exponential)
        );

        return $sign->mul($y);
    }
}