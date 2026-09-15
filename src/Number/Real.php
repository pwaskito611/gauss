<?php

declare(strict_types=1);

namespace Gauss\Number;

use DivisionByZeroError;

final class Real implements NumericValue
{
    private const SCALE = 50;
    private const INTERNAL_SCALE = 60;

    public function __construct(
        private readonly string $number,
    ) {
    }

    public function one(): NumericValue
    {
        return new self('1');
    }

    public function add(NumericValue $other): NumericValue
    {
        return match (true) {
            $other instanceof Complex =>
                $other->add($this),

            $other instanceof Irrational =>
                $other->add($this),

            default =>
                new self(
                    $this->normalize(
                        bcadd(
                            $this->number,
                            $this->operandValue($other),
                            self::INTERNAL_SCALE
                        )
                    )
                ),
        };
    }

    public function sub(NumericValue $other): NumericValue
    {
        return match (true) {
            $other instanceof Complex =>
                (new Complex($this->number, '0'))->sub($other),

            $other instanceof Irrational =>
                (new Irrational($this->number))->sub($other),

            default =>
                new self(
                    $this->normalize(
                        bcsub(
                            $this->number,
                            $this->operandValue($other),
                            self::INTERNAL_SCALE
                        )
                    )
                ),
        };
    }

    public function mul(NumericValue $other): NumericValue
    {
        return match (true) {
            $other instanceof Complex =>
                $other->mul($this),

            $other instanceof Irrational =>
                $other->mul($this),

            default =>
                new self(
                    $this->normalize(
                        bcmul(
                            $this->number,
                            $this->operandValue($other),
                            self::INTERNAL_SCALE
                        )
                    )
                ),
        };
    }

    public function div(NumericValue $other): NumericValue
    {
        return match (true) {
            $other instanceof Complex =>
                (new Complex($this->number, '0'))->div($other),

            $other instanceof Irrational =>
                (new Irrational($this->number))->div($other),

            default =>
                $this->divideReal($other),
        };
    }

    private function divideReal(NumericValue $other): NumericValue
    {
        $otherValue = $this->operandValue($other);

        if (bccomp($otherValue, '0', self::INTERNAL_SCALE) === 0) {
            throw new DivisionByZeroError();
        }

        return new self(
            $this->normalize(
                bcdiv(
                    $this->number,
                    $otherValue,
                    self::INTERNAL_SCALE
                )
            )
        );
    }

    public function value(): string
    {
        return $this->number;
    }

    private function operandValue(NumericValue $other): string
    {
        if ($other instanceof Rational) {
            return bcdiv(
                (string) $other->numerator(),
                (string) $other->denominator(),
                self::INTERNAL_SCALE
            );
        }

        return $other->value();
    }

    private function normalize(string $value): string
    {
        $negative = str_starts_with($value, '-');

        if ($negative) {
            $value = substr($value, 1);
        }

        [$integer, $fraction] = array_pad(
            explode('.', $value, 2),
            2,
            ''
        );

        $fraction = str_pad(
            $fraction,
            self::SCALE + 1,
            '0'
        );

        $roundDigit = (int) $fraction[self::SCALE];

        $fraction = substr(
            $fraction,
            0,
            self::SCALE
        );

        $result = $integer . '.' . $fraction;

        if ($roundDigit >= 5) {
            $result = bcadd(
                $result,
                '0.' . str_repeat('0', self::SCALE - 1) . '1',
                self::SCALE
            );
        }

        if ($negative && bccomp($result, '0', self::SCALE) !== 0) {
            $result = '-' . $result;
        }

        return $result;
    }
}