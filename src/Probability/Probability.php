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

    public static function of(int|float|string|Number $value): self
    {
        $number = Number::of($value);

        if ($number->compare(0) < 0 || $number->compare(1) > 0) {
            throw new InvalidArgumentException('Probability must be between 0 and 1 inclusive.');
        }

        return new self($number);
    }

    public function value(): string
    {
        return $this->value->value();
    }

    public function compare(int|float|string|Number $other): int
    {
        return $this->value->compare(Number::of($other));
    }

    public function complement(): self
    {
        return new self(Number::of(1)->sub($this->value));
    }

    public function add(self $other): self
    {
        return new self(Number::of($this->value->value())->add($other->value));
    }

    public function multiply(self $other): self
    {
        return new self(Number::of($this->value->value())->mul($other->value));
    }

    public function divide(self $other): self
    {
        if ($other->isZero()) {
            throw new \DivisionByZeroError('Cannot divide by zero probability.');
        }

        return new self(Number::of($this->value->value())->div($other->value));
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
