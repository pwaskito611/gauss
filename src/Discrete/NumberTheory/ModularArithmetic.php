<?php

declare(strict_types=1);

namespace Gauss\Discrete\NumberTheory;

use Gauss\Number\Number;
use InvalidArgumentException;

final class ModularArithmetic
{
    public static function add(Number $a, Number $b, Number $modulus): Number
    {
        self::assertInputs($a, $b, $modulus);
        return self::normalize($a->add($b), $modulus);
    }

    public static function subtract(Number $a, Number $b, Number $modulus): Number
    {
        self::assertInputs($a, $b, $modulus);
        return self::normalize($a->sub($b), $modulus);
    }

    public static function multiply(Number $a, Number $b, Number $modulus): Number
    {
        self::assertInputs($a, $b, $modulus);
        return self::normalize($a->mul($b), $modulus);
    }

    public static function power(Number $a, Number $exponent, Number $modulus): Number
    {
        self::assertInputs($a, $exponent, $modulus);
        if ($exponent->compare(Number::of(0)) < 0) {
            throw new InvalidArgumentException('Modular power exponent must be non-negative.');
        }

        $result = Number::of(1);
        $base = self::normalize($a, $modulus);
        $power = $exponent;

        while ($power->compare(Number::of(0)) > 0) {
            if ($power->mod(2)->compare(Number::of(0)) === 0) {
                $base = self::normalize($base->mul($base), $modulus);
                $power = Number::of((string) (int) bcdiv($power->value(), '2', 0));
            } else {
                $result = self::normalize($result->mul($base), $modulus);
                $power = $power->sub(1);
            }
        }

        return $result;
    }

    private static function normalize(Number $value, Number $modulus): Number
    {
        $remainder = Divisibility::remainder($value, $modulus);
        if ($remainder->compare(Number::of(0)) < 0) {
            $remainder = $remainder->add($modulus);
        }

        return $remainder;
    }

    private static function assertInputs(Number $a, Number $b, Number $modulus): void
    {
        if (! preg_match('/^-?\d+$/', $a->value()) || ! preg_match('/^-?\d+$/', $b->value()) || ! preg_match('/^-?\d+$/', $modulus->value())) {
            throw new InvalidArgumentException('Modular arithmetic requires integer inputs.');
        }

        if ($modulus->compare(Number::of(0)) <= 0) {
            throw new InvalidArgumentException('Modulus must be positive.');
        }
    }
}
