<?php

declare(strict_types=1);

namespace Gauss\Number;

use InvalidArgumentException;
use LogicException;

final class Number implements NumericValue
{
    private function __construct(
        private readonly NumericValue $value,
    ) {
    }

    public static function of(int|float|string|NumericValue $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

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

    public function one(): NumericValue
    {
        return new self($this->value->one());
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

    public function mod(int|float|string|NumericValue $other): self
    {
        $divisor = self::of($other);
        if ($divisor->compare(0) === 0) {
            throw new DivisionByZeroError('Division by zero is undefined.');
        }

        if (! preg_match('/^-?\d+$/', $this->value()) || ! preg_match('/^-?\d+$/', $divisor->value())) {
            throw new InvalidArgumentException('Modulo requires integer values.');
        }

        $quotient = self::integerQuotient($this, $divisor);
        $remainder = $this->sub($quotient->mul($divisor));

        if ($remainder->compare(0) < 0) {
            $direction = $divisor->compare(0) < 0 ? Number::of(1) : Number::of(-1);
            $quotient = $quotient->add($direction);
            $remainder = $this->sub($quotient->mul($divisor));
        }

        return $remainder;
    }

    public function compare(int|float|string|NumericValue $other): int
    {
        $left = $this->decimalValue();
        $right = self::of($other)->decimalValue();

        return bccomp($left, $right, 50);
    }

    public function abs(): self
    {
        if ($this->compare(0) < 0) {
            return $this->mul(-1);
        }

        return $this;
    }

    public function pow(int $exponent): self
    {
        if ($exponent < 0) {
            return $this->one()->div($this->pow(-$exponent));
        }

        $result = $this->one();
        $factor = $this;
        $remaining = $exponent;

        while ($remaining > 0) {
            if (($remaining & 1) === 1) {
                $result = $result->mul($factor);
            }

            $remaining >>= 1;
            if ($remaining > 0) {
                $factor = $factor->mul($factor);
            }
        }

        return $result;
    }

    public function sqrt(): self
    {
        if ($this->compare(0) < 0) {
            throw new LogicException('Square root requires a non-negative real number.');
        }

        if ($this->compare(0) === 0) {
            return $this;
        }

        $radicand = self::of($this->decimalValue());
        $guess = self::of('1.0');
        $two = self::of('2.0');

        for ($iteration = 0; $iteration < 100; $iteration++) {
            $next = $guess->add($radicand->div($guess))->div($two);
            if ($next->value() === $guess->value()) {
                return $next;
            }
            $guess = $next;
        }

        return $guess;
    }

    public function exp(): self
    {
        $x = $this->decimalValue();

        if (bccomp($x, '0', 60) === 0) {
            return self::of(1);
        }

        $squares = 0;
        $reduced = $x;
        $one = '1.000000000000000000000000000000000000000000000000000000000000';

        while (bccomp($reduced, $one, 60) > 0 || bccomp($reduced, bcsub('0', $one, 60), 60) < 0) {
            $reduced = bcdiv($reduced, '2', 60);
            $squares++;
        }

        $result = self::of($this->taylorExp($reduced));

        for ($i = 0; $i < $squares; $i++) {
            $result = $result->mul($result);
        }

        return $result;
    }

    private function taylorExp(string $x): string
    {
        $scale = 60;
        $term = '1';
        $sum = '1';
        $tolerance = '0.' . str_repeat('0', 59) . '1';

        for ($n = 1; $n <= 400; $n++) {
            $term = bcmul(
                $term,
                bcdiv($x, (string) $n, $scale),
                $scale
            );
            $sum = bcadd($sum, $term, $scale);

            if (bccomp($term, '0', $scale) >= 0) {
                if (bccomp($term, $tolerance, $scale) < 0) {
                    break;
                }
            } elseif (bccomp($term, bcsub('0', $tolerance, $scale), $scale) > 0) {
                break;
            }
        }

        return $sum;
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

    private function decimalValue(): string
    {
        if ($this->value instanceof Rational) {
            return bcdiv(
                (string) $this->value->numerator(),
                (string) $this->value->denominator(),
                60
            );
        }

        if ($this->value instanceof Real) {
            return $this->value->value();
        }

        throw new LogicException('Comparison requires a real numeric value.');
    }

    private static function integerQuotient(self $left, self $right): self
    {
        $leftValue = $left->value();
        $rightValue = $right->value();

        $leftAbs = ltrim($leftValue, '-');
        $rightAbs = ltrim($rightValue, '-');

        $quotient = bcdiv($leftAbs, $rightAbs, 0);
        $sign = (str_starts_with($leftValue, '-') xor str_starts_with($rightValue, '-'))
            ? '-' : '';

        if ($leftValue === '0' || $quotient === '0') {
            return self::of(0);
        }

        return self::of($sign . $quotient);
    }
}