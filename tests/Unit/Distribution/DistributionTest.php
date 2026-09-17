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
        $distribution = Bernoulli::of('1/2');

        self::assertSame('1/2', $distribution->pmf(1)->value());
        self::assertSame('1/2', $distribution->pmf(0)->value());
        self::assertSame('1/2', $distribution->cdf(0)->value());
        self::assertSame('1/2', $distribution->expectation()->value());
        self::assertSame('1/4', $distribution->variance()->value());
    }

    public function testBinomialDistribution(): void
    {
        $distribution = Binomial::of(5, '1/2');

        self::assertSame('5/16', $distribution->pmf(2)->value());
        self::assertSame('1/2', $distribution->cdf(2)->value());
        self::assertSame('5/2', $distribution->expectation()->value());
        self::assertSame('5/4', $distribution->variance()->value());
    }

    public function testGeometricDistribution(): void
    {
        $distribution = Geometric::of('1/2');

        self::assertSame('1/2', $distribution->pmf(1)->value());
        self::assertSame('1/4', $distribution->pmf(2)->value());
        self::assertSame('3/4', $distribution->cdf(2)->value());
        self::assertSame('2', $distribution->expectation()->value());
        self::assertSame('2', $distribution->variance()->value());
    }

    public function testPoissonDistribution(): void
    {
        $distribution = Poisson::of(3);

        self::assertSame('3', $distribution->expectation()->value());
        self::assertSame('3', $distribution->variance()->value());
        self::assertSame(0, Number::of($distribution->pmf(0)->value())->compare('0.04978706836786394297934241565006177663169959218842'));
    }

    public function testUniformDistribution(): void
    {
        $distribution = Uniform::of(0, 2);

        self::assertSame('1/2', $distribution->pdf(1)->value());
        self::assertSame('1/2', $distribution->cdf(1)->value());
        self::assertSame('1', $distribution->expectation()->value());
        self::assertSame('1/3', $distribution->variance()->value());
    }

    public function testNormalDistribution(): void
    {
        $distribution = Normal::of(0, 1);

        self::assertSame('0', $distribution->expectation()->value());
        self::assertSame('1', $distribution->variance()->value());
        self::assertSame(0, Number::of($distribution->cdf(0)->value())->compare('0.5'));
    }

    public function testExponentialDistribution(): void
    {
        $distribution = Exponential::of(2);

        self::assertSame(0, $distribution->pdf(0)->compare('2'));
        self::assertSame('1/2', $distribution->expectation()->value());
        self::assertSame('1/4', $distribution->variance()->value());
        self::assertSame(0, Number::of($distribution->cdf(1)->value())->compare('0.86466471676338730810600050502751559659236845409042'));
    }
}
