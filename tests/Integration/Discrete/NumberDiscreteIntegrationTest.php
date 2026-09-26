<?php

declare(strict_types=1);

namespace Gauss\Tests\Integration\Discrete;

use Gauss\Discrete\NumberTheory\ExtendedGCD;
use Gauss\Discrete\NumberTheory\GCD;
use Gauss\Discrete\NumberTheory\LCM;
use Gauss\Discrete\NumberTheory\ModularArithmetic;
use Gauss\Discrete\NumberTheory\ModularInverse;
use Gauss\Number\Number;
use PHPUnit\Framework\TestCase;

final class NumberDiscreteIntegrationTest extends TestCase
{
    public function testItCarriesLargeNumbersThroughGcdLcmAndModularInverse(): void
    {
        $large = Number::of('100000000000000000001');
        $modulus = Number::of(101);
        $extended = ExtendedGCD::of($large, $modulus);
        $inverse = ModularInverse::of($large, $modulus);

        self::assertSame('1', GCD::of($large, Number::of(36))->value());
        self::assertSame('3600000000000000000036', LCM::of($large, Number::of(36))->value());
        self::assertSame('1', $extended->gcd()->value());
        self::assertSame('51', $inverse->value());
        self::assertSame('1', ModularArithmetic::multiply($large, $inverse, $modulus)->value());
    }
}