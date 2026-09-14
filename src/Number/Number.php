<?php

declare(strict_types=1);

namespace Gauss\Number;

use InvalidArgumentException;

final class Number
{
    private function __construct(
        private readonly NumericValue $value,
    ) {
    }

    public static function of(int|float|string|NumericValue $value): self
    {
        if ($value instanceof NumericValue) {
            return new self($value);
        }

        if (is_int($value)) {
            return new self(
                new Rational($value, 1)
            );
        }

        if (is_float($value)) {
            return new self(
                new Real((string) $value)
            );
        }

        return new self(
            self::parse($value)
        );
    }

    private static function parse(string $value): NumericValue
    {
        $value = trim($value);

        // Rational: 1/2, -3/4
        if (
            preg_match(
                '/^([+-]?\d+)\/([+-]?\d+)$/',
                $value,
                $match
            )
        ) {
            return new Rational(
                (int) $match[1],
                (int) $match[2]
            );
        }

        // Complex: 2+3i, 2-3i, -2+4i, -2-4i
        if (
            preg_match(
                '/^([+-]?\d+(?:\.\d+)?)([+-])(\d+(?:\.\d+)?)i$/',
                $value,
                $match
            )
        ) {
            $imaginary = $match[2] === '-'
                ? "-{$match[3]}"
                : $match[3];

            return new Complex(
                $match[1],
                $imaginary
            );
        }

        // Irrational expressions: pi, e, sqrt(...)
        if (
            $value === 'pi'
            || $value === 'e'
            || preg_match('/^sqrt\(.+\)$/', $value)
        ) {
            return new Irrational($value);
        }

        // Integer written as string
        if (preg_match('/^[+-]?\d+$/', $value)) {
            return new Rational(
                (int) $value,
                1
            );
        }

        // Decimal
        if (is_numeric($value)) {
            return new Real($value);
        }

        throw new InvalidArgumentException(
            "Unsupported numeric value: {$value}"
        );
    }

    public function add(int|float|string|NumericValue $other): self
    {
        return new self(
            $this->value->add(
                self::of($other)->value
            )
        );
    }

    public function sub(int|float|string|NumericValue $other): self
    {
        return new self(
            $this->value->sub(
                self::of($other)->value
            )
        );
    }

    public function mul(int|float|string|NumericValue $other): self
    {
        return new self(
            $this->value->mul(
                self::of($other)->value
            )
        );
    }

    public function div(int|float|string|NumericValue $other): self
    {
        return new self(
            $this->value->div(
                self::of($other)->value
            )
        );
    }

    public function value(): string
    {
        return $this->value->value();
    }

    public function type(): string
    {
        return $this->value::class;
    }

    public function __toString(): string
    {
        return $this->value();
    }
}