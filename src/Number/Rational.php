<?php

declare(strict_types=1);

namespace Gauss\Number;

use DivisionByZeroError;

final class Rational implements NumericValue
{
    private const SCALE = 50;
    private const INTERNAL_SCALE = 60;

    private readonly int $numerator;
    private readonly int $denominator;

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
        if ($other instanceof self) {
            return new self(
                $this->numerator * $other->denominator
                    + $other->numerator * $this->denominator,
                $this->denominator * $other->denominator
            );
        }

        return $this->promote($other)->add($other);
    }

    public function sub(NumericValue $other): NumericValue
    {
        if ($other instanceof self) {
            return new self(
                $this->numerator * $other->denominator
                    - $other->numerator * $this->denominator,
                $this->denominator * $other->denominator
            );
        }

        return $this->promote($other)->sub($other);
    }

    public function mul(NumericValue $other): NumericValue
    {
        if ($other instanceof self) {
            return new self(
                $this->numerator * $other->numerator,
                $this->denominator * $other->denominator
            );
        }

        return $this->promote($other)->mul($other);
    }

    public function div(NumericValue $other): NumericValue
    {
        if ($other instanceof self) {
            if ($other->numerator === 0) {
                throw new DivisionByZeroError();
            }

            return new self(
                $this->numerator * $other->denominator,
                $this->denominator * $other->numerator
            );
        }

        return $this->promote($other)->div($other);
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
                new Complex($this->toDecimal(), '0'),

            $other instanceof Irrational =>
                new Irrational($this->value()),

            default =>
                new Real($this->toDecimal()),
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

    private static function gcd(int $a, int $b): int
    {
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }

        return $a;
    }
}