<?php

declare(strict_types=1);

namespace Gauss\Tests\Feature\Discrete;

use Gauss\Discrete\NumberTheory\ExtendedGCD;
use Gauss\Discrete\NumberTheory\ModularArithmetic;
use Gauss\Discrete\NumberTheory\ModularInverse;
use Gauss\Number\Number;
use PHPUnit\Framework\TestCase;

final class DiscreteFeatureTest extends TestCase
{
    public function testItBuildsAndVerifiesAModularInverseFromBezoutCoefficients(): void
    {
        $value = Number::of(-3);
        $modulus = Number::of(11);
        $bezout = ExtendedGCD::of($value, $modulus);
        $inverse = ModularInverse::of($value, $modulus);
        $product = ModularArithmetic::multiply($value, $inverse, $modulus);

        self::assertSame('1', $bezout->gcd()->value());
        self::assertSame('7', $inverse->value());
        self::assertSame('1', $product->value());
    }
}