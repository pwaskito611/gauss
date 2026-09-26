<?php

declare(strict_types=1);

namespace Gauss\Tests\Integration\Probability;

use Gauss\Distribution\Binomial;
use Gauss\Probability\Probability;
use PHPUnit\Framework\TestCase;

final class BinomialProbabilityIntegrationTest extends TestCase
{
    public function testItComposesBinomialCdfAndProbabilityComplement(): void
    {
        $distribution = Binomial::of(2, '0.5');
        $atMostOneSuccess = $distribution->cdf(1);
        $twoSuccesses = $distribution->pmf(2);

        self::assertInstanceOf(Probability::class, $atMostOneSuccess);
        self::assertSame(0, $atMostOneSuccess->value()->compare('0.75'));
        self::assertSame(0, $atMostOneSuccess->complement()->value()->compare($twoSuccesses->value()));
    }
}