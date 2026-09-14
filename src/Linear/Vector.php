<?php

declare(strict_types=1);

namespace Gauss\Linear;

use Gauss\Number\Number;
use Gauss\Number\NumericValue;
use InvalidArgumentException;
use LogicException;

/**
 * Immutable finite-dimensional vector over NumericValue.
 */
final class Vector
{
    /** @var list<NumericValue> */
    private readonly array $values;

    private function __construct(array $values)
    {
        if ($values === []) {
            throw new InvalidArgumentException('Vector must contain at least one value.');
        }

        foreach ($values as $value) {
            if (! $value instanceof NumericValue) {
                throw new InvalidArgumentException('Vector values must be NumericValue instances.');
            }
        }

        $this->values = array_values($values);
    }

    public static function of(int|float|string|NumericValue ...$values): self
    {
        return new self(array_map(
            static fn (int|float|string|NumericValue $value): NumericValue => Number::of($value),
            $values
        ));
    }

    public static function zero(int $dimension): self
    {
        if ($dimension < 1) {
            throw new InvalidArgumentException('Vector dimension must be >= 1.');
        }

        return new self(array_fill(0, $dimension, Number::of(0)));
    }

    public static function basis(int $dimension, int $index): self
    {
        if ($dimension < 1) {
            throw new InvalidArgumentException('Vector dimension must be >= 1.');
        }

        if ($index < 0 || $index >= $dimension) {
            throw new InvalidArgumentException('Basis index is outside the vector dimension.');
        }

        $values = array_fill(0, $dimension, Number::of(0));
        $values[$index] = Number::of(1);

        return new self($values);
    }

    public function dimension(): int
    {
        return count($this->values);
    }

    public function get(int $index): NumericValue
    {
        if ($index < 0 || $index >= $this->dimension()) {
            throw new InvalidArgumentException('Vector index is outside the vector dimension.');
        }

        return $this->values[$index];
    }

    /** @return list<NumericValue> */
    public function values(): array
    {
        return $this->values;
    }

    public function add(self $other): self
    {
        $this->assertSameDimension($other);

        $values = [];
        foreach ($this->values as $index => $value) {
            $values[] = $value->add($other->values[$index]);
        }

        return new self($values);
    }

    public function sub(self $other): self
    {
        $this->assertSameDimension($other);

        $values = [];
        foreach ($this->values as $index => $value) {
            $values[] = $value->sub($other->values[$index]);
        }

        return new self($values);
    }

    public function scale(NumericValue $scalar): self
    {
        return new self(array_map(
            static fn (NumericValue $value): NumericValue => $value->mul($scalar),
            $this->values
        ));
    }

    public function negate(): self
    {
        return $this->scale(Number::of(-1));
    }

    public function dot(self $other): NumericValue
    {
        $this->assertSameDimension($other);
        $result = $this->values[0]->sub($this->values[0]);

        foreach ($this->values as $index => $value) {
            $result = $result->add($value->mul($other->values[$index]));
        }

        return $result;
    }

    public function cross(self $other): self
    {
        if ($this->dimension() !== 3 || $other->dimension() !== 3) {
            throw new InvalidArgumentException('Cross product requires two three-dimensional vectors.');
        }

        return new self([
            $this->values[1]->mul($other->values[2])->sub($this->values[2]->mul($other->values[1])),
            $this->values[2]->mul($other->values[0])->sub($this->values[0]->mul($other->values[2])),
            $this->values[0]->mul($other->values[1])->sub($this->values[1]->mul($other->values[0])),
        ]);
    }

    public function normSquared(): NumericValue
    {
        return $this->dot($this);
    }

    public function norm(): NumericValue
    {
        $squaredValue = $this->normSquared()->value();
        if ($this->isZeroRepresentation($squaredValue)) {
            return $this->normSquared()->sub($this->normSquared());
        }

        if (str_contains($squaredValue, '/') || str_contains($squaredValue, 'i')) {
            throw new LogicException('Vector norm requires a real-valued NumericValue.');
        }

        $squared = Number::of(
            str_contains($squaredValue, '.') ? $squaredValue : $squaredValue . '.0'
        );
        $zero = $squared->sub($squared);

        if ($this->isZeroRepresentation($squared->value())) {
            return $zero;
        }

        if ($squared->type() !== \Gauss\Number\Real::class) {
            throw new LogicException('Vector norm requires a real-valued NumericValue.');
        }

        $guess = Number::of('1.0');
        $two = Number::of('2.0');

        for ($iteration = 0; $iteration < 100; $iteration++) {
            $next = $guess->add($squared->div($guess))->div($two);
            if ($next->value() === $guess->value()) {
                break;
            }
            $guess = $next;
        }

        return $guess;
    }

    public function normalize(): self
    {
        $norm = $this->norm();
        if ($this->isZeroRepresentation($norm->value())) {
            throw new LogicException('Cannot normalize the zero vector.');
        }

        return $this->scale($norm->one()->div($norm));
    }

    public function distance(self $other): NumericValue
    {
        return $this->sub($other)->norm();
    }

    public function isZero(): bool
    {
        $zero = $this->values[0]->sub($this->values[0]);

        foreach ($this->values as $value) {
            if ($value->value() !== $zero->value()) {
                return false;
            }
        }

        return true;
    }

    public function isOrthogonalTo(self $other): bool
    {
        return $this->dot($other)->value() === $this->dot($other)->sub($this->dot($other))->value();
    }

    public function isParallelTo(self $other): bool
    {
        $this->assertSameDimension($other);
        if ($this->isZero() || $other->isZero()) {
            return true;
        }

        if ($this->dimension() === 3) {
            return $this->cross($other)->isZero();
        }

        $ratio = null;
        foreach ($this->values as $index => $value) {
            if ($value->value() === '0') {
                if ($other->values[$index]->value() !== '0') {
                    return false;
                }
                continue;
            }

            $current = $other->values[$index]->div($value);
            $ratio ??= $current->value();
            if ($current->value() !== $ratio) {
                return false;
            }
        }

        return true;
    }

    public function map(callable $transform): self
    {
        return new self(array_map(
            static fn (NumericValue $value): NumericValue => Number::of($transform($value)),
            $this->values
        ));
    }

    private function assertSameDimension(self $other): void
    {
        if ($this->dimension() !== $other->dimension()) {
            throw new InvalidArgumentException('Vector dimensions must match.');
        }
    }

    private function isZeroRepresentation(string $value): bool
    {
        $value = ltrim($value, '+-');
        $value = str_replace('.', '', $value);

        return $value !== '' && trim($value, '0') === '';
    }
}
