<?php

declare(strict_types=1);

namespace Gauss\Linear;

use Gauss\Number\Number;
use InvalidArgumentException;
use LogicException;

/**
 * Immutable finite-dimensional vector over Number.
 */
final class Vector
{
    /** @var list<Number> */
    private readonly array $values;

    private function __construct(array $values)
    {
        if ($values === []) {
            throw new InvalidArgumentException('Vector must contain at least one value.');
        }

        foreach ($values as $value) {
            if (! $value instanceof Number) {
                throw new InvalidArgumentException('Vector values must be Number instances.');
            }
        }

        $this->values = array_values($values);
    }

    public static function of(int|float|string|Number ...$values): self
    {
        return new self(array_map(
            static fn (int|float|string|Number $value): Number => Number::of($value),
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

    public function get(int $index): Number
    {
        if ($index < 0 || $index >= $this->dimension()) {
            throw new InvalidArgumentException('Vector index is outside the vector dimension.');
        }

        return $this->values[$index];
    }

    /** @return list<Number> */
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

    public function scale(Number $scalar): self
    {
        return new self(array_map(
            static fn (Number $value): Number => $value->mul($scalar),
            $this->values
        ));
    }

    public function negate(): self
    {
        return $this->scale(Number::of(-1));
    }

    public function dot(self $other): Number
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

    public function normSquared(): Number
    {
        return $this->dot($this);
    }

    /**
    * Returns the square root of the squared norm in the Number domain.
     *
     * The underlying domain must be able to represent the result.
     */
    public function norm(): Number
    {
        $squared = $this->normSquared();
        if ($this->isZeroValue($squared)) {
            return $squared;
        }

        if ($squared->compare(0) < 0) {
            throw new LogicException('Vector norm requires a real-valued Number.');
        }

        return $squared->sqrt();
    }

    public function normalize(): self
    {
        $norm = $this->norm();
        if ($this->isZeroValue($norm)) {
            throw new LogicException('Cannot normalize the zero vector.');
        }

        return $this->scale($norm->one()->div($norm));
    }

    public function distance(self $other): Number
    {
        return $this->sub($other)->norm();
    }

    public function isZero(): bool
    {
        foreach ($this->values as $value) {
            if (! $this->isZeroValue($value)) {
                return false;
            }
        }

        return true;
    }

    public function isOrthogonalTo(self $other): bool
    {
        return $this->isZeroValue($this->dot($other));
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
            if ($this->isZeroValue($value)) {
                if (! $this->isZeroValue($other->values[$index])) {
                    return false;
                }
                continue;
            }

            $current = $other->values[$index]->div($value);
            $ratio ??= $current;
            if ($current->compare($ratio) !== 0) {
                return false;
            }
        }

        return true;
    }

    public function map(callable $transform): self
    {
        return new self(array_map(
            static fn (Number $value): Number => Number::of($transform($value)),
            $this->values
        ));
    }

    private function assertSameDimension(self $other): void
    {
        if ($this->dimension() !== $other->dimension()) {
            throw new InvalidArgumentException('Vector dimensions must match.');
        }
    }

    private static function isZeroValue(Number $value): bool
    {
        return $value->compare(0) === 0;
    }
}
