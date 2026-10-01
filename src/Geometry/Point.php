<?php

declare(strict_types=1);

namespace Gauss\Geometry;

use Gauss\Linear\Vector;
use Gauss\Number\Number;
use InvalidArgumentException;
use LogicException;

/** A point represented by coordinates in the Number domain. */
final class Point
{
    private function __construct(
        private readonly Vector $coordinates,
    ) {
    }

    public static function of(int|float|string|Number ...$coordinates): self
    {
        if ($coordinates === []) {
            throw new InvalidArgumentException('Point requires at least one coordinate value.');
        }

        return new self(Vector::of(...$coordinates));
    }

    public function coordinates(): Vector
    {
        return $this->coordinates;
    }

    /**
     * Returns the Euclidean distance; the Number domain must support its sqrt.
     */
    public function distanceTo(self $other): Number
    {
        return $this->coordinates->distance($other->coordinates);
    }

    public function vectorTo(self $other): Vector
    {
        return $other->coordinates->sub($this->coordinates);
    }

    /**
     * Returns the classical midpoint and therefore requires division by 2.
     * It is undefined in characteristic 2, where 2 is zero.
     */
    public function midpoint(self $other): self
    {
        $one = Number::of(1);
        $two = Number::of(2);
        $zero = Number::of(0);
        if ($two->compare($zero) === 0) {
            throw new LogicException('Midpoint is undefined in characteristic 2.');
        }
        $half = $one->div($two);

        return new self(
            $this->coordinates->add($other->coordinates)->scale($half)
        );
    }

    public function translate(Vector $vector): self
    {
        return new self(
            $this->coordinates->add($vector)
        );
    }

    public function equals(self $other): bool
    {
        if ($this->coordinates->dimension() !== $other->coordinates->dimension()) {
            return false;
        }

        foreach ($this->coordinates->values() as $index => $value) {
            if ($value->compare($other->coordinates->get($index)) !== 0) {
                return false;
            }
        }

        return true;
    }
}
