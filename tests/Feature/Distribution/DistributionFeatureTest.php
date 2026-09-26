<?php

declare(strict_types=1);

namespace Gauss\Tests\Feature\Distribution;

use Gauss\Distribution\Binomial;
use Gauss\Distribution\Normal;
use Gauss\Number\Number;
use PHPUnit\Framework\TestCase;

final class DistributionFeatureTest extends TestCase
{
    public function testItEvaluatesDiscreteAndContinuousDistributionFunctions(): void
    {
        $binomial = Binomial::of(2, '0.5');
        $normal = Normal::of(0, 1);

        self::assertSame(0, $binomial->pmf(1)->value()->compare('0.5'));
        self::assertSame(0, $binomial->cdf(1)->value()->compare('0.75'));
        self::assertSame(0, $normal->cdf(0)->compare('0.5'));
        self::assertLessThanOrEqual(0, $normal->pdf(0)->sub('0.39894228')->abs()->compare('0.000001'));
        self::assertInstanceOf(Number::class, $normal->pdf(0));
    }
}