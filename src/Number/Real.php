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
                $this->calculate(
                    $this->operandValue($other),
                    'add'
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
                $this->calculate(
                    $this->operandValue($other),
                    'sub'
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
                $this->calculate(
                    $this->operandValue($other),
                    'mul'
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
                $this->divideReal($this->operandValue($other)),
        };
    }

    public function value(): string
    {
        return $this->number;
    }

    private function calculate(
        string $other,
        string $operation,
    ): self {
        $result = match ($operation) {
            'add' => bcadd(
                $this->number,
                $other,
                self::INTERNAL_SCALE
            ),

            'sub' => bcsub(
                $this->number,
                $other,
                self::INTERNAL_SCALE
            ),

            'mul' => bcmul(
                $this->number,
                $other,
                self::INTERNAL_SCALE
            ),

            default => throw new \LogicException(
                "Unsupported real operation: {$operation}"
            ),
        };

        return new self(
            $this->normalize($result)
        );
    }

    private function divideReal(string $other): self
    {
        if (
            bccomp(
                $other,
                '0',
                self::INTERNAL_SCALE
            ) === 0
        ) {
            throw new DivisionByZeroError();
        }

        return new self(
            $this->normalize(
                bcdiv(
                    $this->number,
                    $other,
                    self::INTERNAL_SCALE
                )
            )
        );
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

        if (
            $negative
            && bccomp($result, '0', self::SCALE) !== 0
        ) {
            $result = '-' . $result;
        }

        return $result;
    }
}