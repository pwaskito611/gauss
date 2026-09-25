<?php

declare(strict_types=1);

namespace Gauss\Probability;

use Gauss\Number\Number;
use InvalidArgumentException;

final class Probability
{
    private function __construct(
        private readonly Number $value,
    ) {
    }

    public static function of(int|float|string|Number|self $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        $number = Number::of($value);

        if ($number->compare(0) < 0 || $number->compare(1) > 0) {
            throw new InvalidArgumentException('Probability must be between 0 and 1 inclusive.');
        }

        return new self($number);
    }

    public function value(): Number
    {
        return $this->value;
    }

    public function compare(int|float|string|Number|self $other): int
    {
        return $this->value->compare(
            $other instanceof self ? $other->value : Number::of($other)
        );
    }

    public function complement(): self
    {
        return self::of($this->value->one()->sub($this->value));
    }

    /** Adds probability values; results outside [0, 1] are rejected. */
    public function add(self $other): self
    {
        return self::of($this->value->add($other->value));
    }

    public function multiply(self $other): self
    {
        return self::of($this->value->mul($other->value));
    }

    /**
     * Returns the general numeric ratio of two probabilities.
     * The result is a Number and may be greater than 1.
     */
    public function divide(self $other): Number
    {
        if ($other->isZero()) {
            throw new \DivisionByZeroError('Cannot divide by zero probability.');
        }

        return $this->value->div($other->value);
    }

    public function isUnit(): bool
    {
        return $this->compare(1) === 0;
    }

    public function isZero(): bool
    {
        return $this->compare(0) === 0;
    }
}
