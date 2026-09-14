<?php

declare(strict_types=1);

namespace Gauss\Number;

use DivisionByZeroError;

final class Complex implements NumericValue
{
    private const SCALE = 50;
    private const INTERNAL_SCALE = 60;

    public function __construct(
        private readonly string $real,
        private readonly string $imaginary,
    ) {
    }

    public function add(NumericValue $other): NumericValue
    {
        $other = self::complex($other);

        return new self(
            bcadd($this->real, $other->real, self::SCALE),
            bcadd($this->imaginary, $other->imaginary, self::SCALE)
        );
    }

    public function sub(NumericValue $other): NumericValue
    {
        $other = self::complex($other);

        return new self(
            bcsub($this->real, $other->real, self::SCALE),
            bcsub($this->imaginary, $other->imaginary, self::SCALE)
        );
    }

    public function mul(NumericValue $other): NumericValue
    {
        $other = self::complex($other);

        $real = bcsub(
            bcmul($this->real, $other->real, self::SCALE),
            bcmul($this->imaginary, $other->imaginary, self::SCALE),
            self::SCALE
        );

        $imaginary = bcadd(
            bcmul($this->real, $other->imaginary, self::SCALE),
            bcmul($this->imaginary, $other->real, self::SCALE),
            self::SCALE
        );

        return new self($real, $imaginary);
    }

    public function div(NumericValue $other): NumericValue
    {
        $other = self::complex($other);

        $denominator = bcadd(
            bcmul($other->real, $other->real, self::SCALE),
            bcmul($other->imaginary, $other->imaginary, self::SCALE),
            self::SCALE
        );

        if (bccomp($denominator, '0', self::SCALE) === 0) {
            throw new DivisionByZeroError();
        }

        $real = bcdiv(
            bcadd(
                bcmul($this->real, $other->real, self::SCALE),
                bcmul($this->imaginary, $other->imaginary, self::SCALE),
                self::SCALE
            ),
            $denominator,
            self::SCALE
        );

        $imaginary = bcdiv(
            bcsub(
                bcmul($this->imaginary, $other->real, self::SCALE),
                bcmul($this->real, $other->imaginary, self::SCALE),
                self::SCALE
            ),
            $denominator,
            self::SCALE
        );

        return new self($real, $imaginary);
    }

    public function value(): string
    {
        if ($this->imaginary === '0') {
            return $this->real;
        }

        if (str_starts_with($this->imaginary, '-')) {
            return "{$this->real}{$this->imaginary}i";
        }

        return "{$this->real}+{$this->imaginary}i";
    }

    public function real(): string
    {
        return $this->real;
    }

    public function imaginary(): string
    {
        return $this->imaginary;
    }

    private static function complex(NumericValue $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if ($value instanceof Rational) {
            return new self(
                self::rationalToDecimal($value),
                '0'
            );
        }

        return new self($value->value(), '0');
    }

    private static function rationalToDecimal(Rational $rational): string
    {
        return bcdiv(
            (string) $rational->numerator(),
            (string) $rational->denominator(),
            self::INTERNAL_SCALE
        );
    }
}