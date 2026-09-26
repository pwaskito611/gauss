<?php

declare(strict_types=1);

namespace Gauss\Tests\Feature\Number;

use Gauss\Number\Number;
use PHPUnit\Framework\TestCase;

final class NumberFeatureTest extends TestCase
{
    public function testItChainsDecimalArithmeticWithoutLeavingNumberDomain(): void
    {
        $result = Number::of('0.123456789123456789')
            ->add('0.000000000000000011')
            ->mul(9)
            ->div(3)
            ->round(18);

        self::assertInstanceOf(Number::class, $result);
        self::assertSame('0.3703703673703704', $result->value());
        self::assertSame(0, $result->compare('0.3703703673703704'));
    }
}