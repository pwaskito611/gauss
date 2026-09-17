<?php

declare(strict_types=1);

namespace Gauss\Number;

use DivisionByZeroError;
use LogicException;

final class Complex implements NumericValue
{
    private const SCALE = 50;
    private const INTERNAL_SCALE = 60;

    public function __construct(
        private readonly string $real,
        private readonly string $imaginary,
    ) {
    }

    public function one(): NumericValue
    {
        return new self('1', '0');
    }

    public function add(NumericValue $other): NumericValue
    {
        $other = self::complex($other);

        return new self(
            self::finalize(
                bcadd(
                    $this->real,
                    $other->real,
                    self::INTERNAL_SCALE
                )
            ),
            self::finalize(
                bcadd(
                    $this->imaginary,
                    $other->imaginary,
                    self::INTERNAL_SCALE
                )
            )
        );
    }

    public function sub(NumericValue $other): NumericValue
    {
        $other = self::complex($other);

        return new self(
            self::finalize(
                bcsub(
                    $this->real,
                    $other->real,
                    self::INTERNAL_SCALE
                )
            ),
            self::finalize(
                bcsub(
                    $this->imaginary,
                    $other->imaginary,
                    self::INTERNAL_SCALE
                )
            )
        );
    }

    public function mul(NumericValue $other): NumericValue
    {
        $other = self::complex($other);

        /*
         * (a + bi)(c + di)
         *
         * real      = ac - bd
         * imaginary = ad + bc
         */
        $real = bcsub(
            bcmul(
                $this->real,
                $other->real,
                self::INTERNAL_SCALE
            ),
            bcmul(
                $this->imaginary,
                $other->imaginary,
                self::INTERNAL_SCALE
            ),
            self::INTERNAL_SCALE
        );

        $imaginary = bcadd(
            bcmul(
                $this->real,
                $other->imaginary,
                self::INTERNAL_SCALE
            ),
            bcmul(
                $this->imaginary,
                $other->real,
                self::INTERNAL_SCALE
            ),
            self::INTERNAL_SCALE
        );

        return new self(
            self::finalize($real),
            self::finalize($imaginary)
        );
    }

    public function div(NumericValue $other): NumericValue
    {
        $other = self::complex($other);

        /*
         * |c + di|² = c² + d²
         */
        $denominator = bcadd(
            bcmul(
                $other->real,
                $other->real,
                self::INTERNAL_SCALE
            ),
            bcmul(
                $other->imaginary,
                $other->imaginary,
                self::INTERNAL_SCALE
            ),
            self::INTERNAL_SCALE
        );

        if (
            bccomp(
                $denominator,
                '0',
                self::INTERNAL_SCALE
            ) === 0
        ) {
            throw new DivisionByZeroError();
        }

        /*
         * (a + bi) / (c + di)
         *
         * real      = (ac + bd) / (c² + d²)
         * imaginary = (bc - ad) / (c² + d²)
         */
        $realNumerator = bcadd(
            bcmul(
                $this->real,
                $other->real,
                self::INTERNAL_SCALE
            ),
            bcmul(
                $this->imaginary,
                $other->imaginary,
                self::INTERNAL_SCALE
            ),
            self::INTERNAL_SCALE
        );

        $imaginaryNumerator = bcsub(
            bcmul(
                $this->imaginary,
                $other->real,
                self::INTERNAL_SCALE
            ),
            bcmul(
                $this->real,
                $other->imaginary,
                self::INTERNAL_SCALE
            ),
            self::INTERNAL_SCALE
        );

        return new self(
            self::finalize(
                bcdiv(
                    $realNumerator,
                    $denominator,
                    self::INTERNAL_SCALE
                )
            ),
            self::finalize(
                bcdiv(
                    $imaginaryNumerator,
                    $denominator,
                    self::INTERNAL_SCALE
                )
            )
        );
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

        if ($value instanceof Irrational) {
            throw new LogicException(
                'Complex and Irrational arithmetic is not supported.'
            );
        }

        return new self(
            $value->value(),
            '0'
        );
    }

    private static function rationalToDecimal(
        Rational $rational
    ): string {
        return bcdiv(
            (string) $rational->numerator(),
            (string) $rational->denominator(),
            self::INTERNAL_SCALE
        );
    }

    private static function finalize(string $value): string
    {
        return bcadd(
            $value,
            '0',
            self::SCALE
        );
    }
}