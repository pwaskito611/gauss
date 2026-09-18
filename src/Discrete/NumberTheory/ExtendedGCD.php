<?php

declare(strict_types=1);

namespace Gauss\Discrete\NumberTheory;

use Gauss\Number\Number;
use InvalidArgumentException;

final class ExtendedGCD
{
    private function __construct(
        private readonly Number $gcd,
        private readonly Number $coefficientX,
        private readonly Number $coefficientY,
    ) {
    }

    public static function of(Number $a, Number $b): self
    {
        self::assertInteger($a);
        self::assertInteger($b);

        if ($a->compare(Number::of(0)) === 0 && $b->compare(Number::of(0)) === 0) {
            return new self(Number::of(0), Number::of(0), Number::of(0));
        }

        $oldRemainder = $a->abs();
        $remainder = $b->abs();
        $oldCoefficientX = Number::of(1);
        $coefficientX = Number::of(0);
        $oldCoefficientY = Number::of(0);
        $coefficientY = Number::of(1);

        while ($remainder->compare(Number::of(0)) !== 0) {
            $quotient = self::quotient($oldRemainder, $remainder);
            $nextRemainder = $oldRemainder->sub($quotient->mul($remainder));
            $nextCoefficientX = $oldCoefficientX->sub($quotient->mul($coefficientX));
            $nextCoefficientY = $oldCoefficientY->sub($quotient->mul($coefficientY));

            $oldRemainder = $remainder;
            $remainder = $nextRemainder;
            $oldCoefficientX = $coefficientX;
            $coefficientX = $nextCoefficientX;
            $oldCoefficientY = $coefficientY;
            $coefficientY = $nextCoefficientY;
        }

        $gcd = $oldRemainder;
        $x = $oldCoefficientX;
        $y = $oldCoefficientY;

        if ($a->compare(Number::of(0)) < 0) {
            $x = $x->mul(Number::of(-1));
        }

        if ($b->compare(Number::of(0)) < 0) {
            $y = $y->mul(Number::of(-1));
        }

        return new self($gcd, $x, $y);
    }

    public function gcd(): Number
    {
        return $this->gcd;
    }

    public function coefficientX(): Number
    {
        return $this->coefficientX;
    }

    public function coefficientY(): Number
    {
        return $this->coefficientY;
    }

    private static function quotient(Number $a, Number $b): Number
    {
        $leftAbs = ltrim($a->value(), '-');
        $rightAbs = ltrim($b->value(), '-');
        return Number::of(bcdiv($leftAbs, $rightAbs, 0));
    }

    private static function assertInteger(Number $value): void
    {
        if (! preg_match('/^-?\d+$/', $value->value())) {
            throw new InvalidArgumentException('ExtendedGCD requires integer values.');
        }
    }
}
