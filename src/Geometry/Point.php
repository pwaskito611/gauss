<?php

declare(strict_types=1);

namespace Gauss\Geometry;

use Gauss\Linear\Vector;
use Gauss\Number\Number;
use InvalidArgumentException;

final class Point
{
    private function __construct(
        private readonly Vector $coordinates,
    ) {
    }

    public static function of(int|float|string| Number ...$coordinates): self
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

    public function distanceTo(self $other): Number
    {
        return $this->coordinates->distance($other->coordinates);
    }

    public function vectorTo(self $other): Vector
    {
        return $other->coordinates->sub($this->coordinates);
    }

    public function midpoint(self $other): self
    {
        $half = Number::of(1)->div(2);

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
            if ($value->value() !== $other->coordinates->get($index)->value()) {
                return false;
            }
        }

        return true;
    }
}
