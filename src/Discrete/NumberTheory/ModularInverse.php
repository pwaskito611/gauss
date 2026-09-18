<?php

declare(strict_types=1);

namespace Gauss\Discrete\NumberTheory;

use Gauss\Number\Number;
use InvalidArgumentException;

final class ModularInverse
{
    public static function of(Number $a, Number $modulus): Number
    {
        self::assertInteger($a);
        self::assertInteger($modulus);

        if ($modulus->compare(Number::of(0)) <= 0) {
            throw new InvalidArgumentException('Modulus must be positive.');
        }

        if ($modulus->compare(Number::of(1)) === 0) {
            return Number::of(0);
        }

        $extended = ExtendedGCD::of($a, $modulus);
        if ($extended->gcd()->compare(Number::of(1)) !== 0) {
            throw new InvalidArgumentException('Modular inverse does not exist for non-coprime inputs.');
        }

        $inverse = $extended->coefficientX();
        if ($a->compare(Number::of(0)) < 0) {
            $inverse = $inverse->mul(Number::of(-1));
        }

        $inverse = $inverse->mod($modulus);
        return $inverse;
    }

    private static function assertInteger(Number $value): void
    {
        if (! preg_match('/^-?\d+$/', $value->value())) {
            throw new InvalidArgumentException('ModularInverse requires integer values.');
        }
    }
}
