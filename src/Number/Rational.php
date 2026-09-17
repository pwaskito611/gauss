<?php

declare(strict_types=1);

namespace Gauss\Number;

use DivisionByZeroError;

final class Rational implements NumericValue
{
    private const INTERNAL_SCALE = 60;

    private readonly int $numerator;
    private readonly int $denominator;

    public function one(): NumericValue
    {
        return new self(1);
    }

    public function __construct(
        int $numerator,
        int $denominator = 1,
    ) {
        if ($denominator === 0) {
            throw new DivisionByZeroError();
        }

        if ($denominator < 0) {
            $numerator *= -1;
            $denominator *= -1;
        }

        $gcd = self::gcd(
            abs($numerator),
            $denominator
        );

        $this->numerator = intdiv($numerator, $gcd);
        $this->denominator = intdiv($denominator, $gcd);
    }

    public function add(NumericValue $other): NumericValue
    {
        if (!$other instanceof self) {
            return $this->promote($other)->add($other);
        }

        $gcd = self::gcd(
            $this->denominator,
            $other->denominator
        );

        $leftDenominator = intdiv(
            $this->denominator,
            $gcd
        );

        $rightDenominator = intdiv(
            $other->denominator,
            $gcd
        );

        try {
            $left = self::safeMultiply(
                $this->numerator,
                $rightDenominator
            );

            $right = self::safeMultiply(
                $other->numerator,
                $leftDenominator
            );

            $numerator = self::safeAdd($left, $right);

            $denominator = self::safeMultiply(
                $leftDenominator,
                $other->denominator
            );

            return new self($numerator, $denominator);
        } catch (\OverflowException) {
            return (new Real($this->toDecimal()))
                ->add(new Real($other->toDecimal()));
        }
    }

    public function sub(NumericValue $other): NumericValue
    {
        if (!$other instanceof self) {
            return $this->promote($other)->sub($other);
        }

        $gcd = self::gcd(
            $this->denominator,
            $other->denominator
        );

        $leftDenominator = intdiv(
            $this->denominator,
            $gcd
        );

        $rightDenominator = intdiv(
            $other->denominator,
            $gcd
        );

        try {
            $left = self::safeMultiply(
                $this->numerator,
                $rightDenominator
            );

            $right = self::safeMultiply(
                $other->numerator,
                $leftDenominator
            );

            $numerator = self::safeSubtract($left, $right);

            $denominator = self::safeMultiply(
                $leftDenominator,
                $other->denominator
            );

            return new self($numerator, $denominator);
        } catch (\OverflowException) {
            return (new Real($this->toDecimal()))
                ->sub(new Real($other->toDecimal()));
        }
    }

    public function mul(NumericValue $other): NumericValue
    {
        if (!$other instanceof self) {
            return $this->promote($other)->mul($other);
        }

        $leftNumerator = $this->numerator;
        $rightNumerator = $other->numerator;

        $leftDenominator = $this->denominator;
        $rightDenominator = $other->denominator;

        /*
         * Cross-cancellation sebelum perkalian:
         *
         * a/b × c/d
         *
         * gcd(a,d)
         * gcd(c,b)
         */
        $gcd = self::gcd(
            $leftNumerator,
            $rightDenominator
        );

        $leftNumerator = intdiv(
            $leftNumerator,
            $gcd
        );

        $rightDenominator = intdiv(
            $rightDenominator,
            $gcd
        );

        $gcd = self::gcd(
            $rightNumerator,
            $leftDenominator
        );

        $rightNumerator = intdiv(
            $rightNumerator,
            $gcd
        );

        $leftDenominator = intdiv(
            $leftDenominator,
            $gcd
        );

        try {
            return new self(
                self::safeMultiply(
                    $leftNumerator,
                    $rightNumerator
                ),
                self::safeMultiply(
                    $leftDenominator,
                    $rightDenominator
                )
            );
        } catch (\OverflowException) {
            return (new Real($this->toDecimal()))
                ->mul(new Real($other->toDecimal()));
        }
    }

    public function div(NumericValue $other): NumericValue
    {
        if (!$other instanceof self) {
            return $this->promote($other)->div($other);
        }

        if ($other->numerator === 0) {
            throw new DivisionByZeroError();
        }

        /*
         * a/b ÷ c/d
         * = a/b × d/c
         */
        $leftNumerator = $this->numerator;
        $rightNumerator = $other->numerator;

        $leftDenominator = $this->denominator;
        $rightDenominator = $other->denominator;

        /*
         * Cross-cancellation:
         *
         * gcd(a,c)
         * gcd(d,b)
         */
        $gcd = self::gcd(
            $leftNumerator,
            $rightNumerator
        );

        $leftNumerator = intdiv(
            $leftNumerator,
            $gcd
        );

        $rightNumerator = intdiv(
            $rightNumerator,
            $gcd
        );

        $gcd = self::gcd(
            $rightDenominator,
            $leftDenominator
        );

        $rightDenominator = intdiv(
            $rightDenominator,
            $gcd
        );

        $leftDenominator = intdiv(
            $leftDenominator,
            $gcd
        );

        try {
            return new self(
                self::safeMultiply(
                    $leftNumerator,
                    $rightDenominator
                ),
                self::safeMultiply(
                    $leftDenominator,
                    $rightNumerator
                )
            );
        } catch (\OverflowException) {
            return (new Real($this->toDecimal()))
                ->div(new Real($other->toDecimal()));
        }
    }

    public function value(): string
    {
        if ($this->denominator === 1) {
            return (string) $this->numerator;
        }

        return "{$this->numerator}/{$this->denominator}";
    }

    public function numerator(): int
    {
        return $this->numerator;
    }

    public function denominator(): int
    {
        return $this->denominator;
    }

    private function promote(NumericValue $other): NumericValue
    {
        return match (true) {
            $other instanceof Complex =>
                new Complex(
                    $this->toDecimal(),
                    '0'
                ),

            $other instanceof Irrational =>
                new Irrational(
                    $this->value()
                ),

            default =>
                new Real(
                    $this->toDecimal()
                ),
        };
    }

    private function toDecimal(): string
    {
        return bcdiv(
            (string) $this->numerator,
            (string) $this->denominator,
            self::INTERNAL_SCALE
        );
    }

    private static function safeMultiply(
        int $left,
        int $right,
    ): int {
        if ($left === 0 || $right === 0) {
            return 0;
        }

        if ($left === 1) {
            return $right;
        }

        if ($right === 1) {
            return $left;
        }

        if ($left === -1) {
            return -$right;
        }

        if ($right === -1) {
            return -$left;
        }

        if (
            $left === PHP_INT_MIN
            || $right === PHP_INT_MIN
        ) {
            if (
                ($left === PHP_INT_MIN && $right === 1)
                || ($right === PHP_INT_MIN && $left === 1)
            ) {
                return PHP_INT_MIN;
            }

            throw new \OverflowException(
                'Integer multiplication overflow.'
            );
        }

        if (
            abs($left) > intdiv(
                PHP_INT_MAX,
                abs($right)
            )
        ) {
            throw new \OverflowException(
                'Integer multiplication overflow.'
            );
        }

        return $left * $right;
    }

    private static function safeAdd(
        int $left,
        int $right,
    ): int {
        if ($right > 0 && $left > PHP_INT_MAX - $right) {
            throw new \OverflowException(
                'Integer addition overflow.'
            );
        }

        if ($right < 0 && $left < PHP_INT_MIN - $right) {
            throw new \OverflowException(
                'Integer addition overflow.'
            );
        }

        return $left + $right;
    }

    private static function safeSubtract(
        int $left,
        int $right,
    ): int {
        if ($right < 0 && $left > PHP_INT_MAX + $right) {
            throw new \OverflowException(
                'Integer subtraction overflow.'
            );
        }

        if ($right > 0 && $left < PHP_INT_MIN + $right) {
            throw new \OverflowException(
                'Integer subtraction overflow.'
            );
        }

        return $left - $right;
    }

    private static function gcd(int $a, int $b): int
    {
        $a = abs($a);

        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }

        return $a;
    }
}