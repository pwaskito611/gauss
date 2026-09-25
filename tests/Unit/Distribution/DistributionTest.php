<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Distribution;

use Gauss\Number\Number;
use Gauss\Distribution\Bernoulli;
use Gauss\Distribution\Binomial;
use Gauss\Distribution\Exponential;
use Gauss\Distribution\Geometric;
use Gauss\Distribution\Normal;
use Gauss\Distribution\Poisson;
use Gauss\Distribution\Uniform;
use Gauss\Probability\Probability;
use PHPUnit\Framework\TestCase;

final class DistributionTest extends TestCase
{
    public function testBernoulliDistribution(): void
    {
        $distribution = Bernoulli::of('0.5');

        self::assertSame('0.5', $distribution->pmf(1)->value()->value());
        self::assertSame('0.5', $distribution->pmf(0)->value()->value());
        self::assertSame('0.5', $distribution->cdf(0)->value()->value());
        self::assertSame('0.5', $distribution->expectation()->value());
        self::assertSame('0.25', $distribution->variance()->value());
    }

    public function testBinomialDistribution(): void
    {
        $distribution = Binomial::of(5, '0.5');

        self::assertSame('0.3125', $distribution->pmf(2)->value()->value());
        self::assertSame('0.5', $distribution->cdf(2)->value()->value());
        self::assertSame('2.5', $distribution->expectation()->value());
        self::assertSame('1.25', $distribution->variance()->value());
    }

    public function testGeometricDistribution(): void
    {
        $distribution = Geometric::of('0.5');

        self::assertSame('0.5', $distribution->pmf(1)->value()->value());
        self::assertSame('0.25', $distribution->pmf(2)->value()->value());
        self::assertSame('0.75', $distribution->cdf(2)->value()->value());
        self::assertSame('2', $distribution->expectation()->value());
        self::assertSame('2', $distribution->variance()->value());
    }

    public function testPoissonDistribution(): void
    {
        $distribution = Poisson::of(3);

        self::assertSame('3', $distribution->expectation()->value());
        self::assertSame('3', $distribution->variance()->value());
        self::assertSame(0, $distribution->pmf(0)->value()->compare('0.04978706836786394297934241565006177663169959218842'));
    }

    public function testUniformDistribution(): void
    {
        $distribution = Uniform::of(0, 2);

        self::assertSame('0.5', $distribution->pdf(1)->value());
        self::assertSame('0.5', $distribution->cdf(1)->value()->value());
        self::assertSame('1', $distribution->expectation()->value());
        self::assertSame('0.3333333333333333333333333333333333333333333333333333333333333', $distribution->variance()->value());
    }

    public function testNormalDistribution(): void
    {
        $distribution = Normal::of(0, 1);

        self::assertSame('0', $distribution->expectation()->value());
        self::assertSame('1', $distribution->variance()->value());
        self::assertSame(0, $distribution->cdf(0)->value()->compare('0.5'));
    }

    public function testExponentialDistribution(): void
    {
        $distribution = Exponential::of(2);

        self::assertSame(0, $distribution->pdf(0)->compare('2'));
        self::assertSame('0.5', $distribution->expectation()->value());
        self::assertSame('0.25', $distribution->variance()->value());
        self::assertSame(0, Number::of($distribution->cdf(1)->value())->compare('0.86466471676338730810600050502751559659236845409042'));
    }
}
