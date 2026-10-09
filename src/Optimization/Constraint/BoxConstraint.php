<?php

declare(strict_types=1);

namespace Gauss\Optimization\Constraint;

use Gauss\Linear\Vector;
use Gauss\Number\Number;
use InvalidArgumentException;

final class BoxConstraint
{
    private function __construct(
        private readonly Vector $lower,
        private readonly Vector $upper,
    ) {
        if ($lower->dimension() !== $upper->dimension()) {
            throw new InvalidArgumentException('Lower and upper bounds must have the same dimension.');
        }

        foreach ($this->lower->values() as $index => $value) {
            if (Number::of($value)->compare($this->upper->get($index)) > 0) {
                throw new InvalidArgumentException('Each lower bound must be less than or equal to the matching upper bound.');
            }
        }
    }

    public static function from(Vector $lower, Vector $upper): self
    {
        return new self($lower, $upper);
    }

    public function lower(): Vector
    {
        return $this->lower;
    }

    public function upper(): Vector
    {
        return $this->upper;
    }

    public function offPrecision(): self
    {
        return new self($this->lower->offPrecision(), $this->upper->offPrecision());
    }

    public function dimension(): int
    {
        return $this->lower->dimension();
    }

    public function contains(Vector $point): bool
    {
        if ($point->dimension() !== $this->dimension()) {
            return false;
        }

        foreach ($this->lower->values() as $index => $lowerValue) {
            $value = Number::of($point->get($index));
            if ($value->compare($lowerValue) < 0 || $value->compare($this->upper->get($index)) > 0) {
                return false;
            }
        }

        return true;
    }
}
