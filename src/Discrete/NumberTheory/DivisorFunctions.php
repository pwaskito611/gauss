<?php

declare(strict_types=1);

namespace Gauss\Discrete\NumberTheory;

use Gauss\Number\Number;
use InvalidArgumentException;

final class DivisorFunctions
{
    public static function tau(Number $n): Number
    {
        self::assertPositiveInteger($n);
        $factors = Factorization::of($n)->exponents();
        $result = Number::of(1);
        foreach ($factors as $exponent) {
            $result = $result->mul($exponent->add(1));
        }

        return $result;
    }

    public static function sigma(Number $n): Number
    {
        self::assertPositiveInteger($n);
        $factors = Factorization::of($n)->primeFactors();
        $exponents = Factorization::of($n)->exponents();
        $result = Number::of(1);

        foreach ($factors as $index => $factor) {
            $power = $factor->pow($exponents[$index]->add(1)->value());
            $base = $factor->sub(1);
            $result = $result->mul($power->sub(1)->div($base));
        }

        return $result;
    }

    private static function assertPositiveInteger(Number $value): void
    {
        if (! preg_match('/^-?\d+$/', $value->value())) {
            throw new InvalidArgumentException('DivisorFunctions require a positive integer.');
        }

        if ($value->compare(Number::of(1)) < 0) {
            throw new InvalidArgumentException('DivisorFunctions require n >= 1.');
        }
    }
}
