<?php

declare(strict_types=1);

namespace Gauss\Geometry;

use Gauss\Linear\Vector;
use Gauss\Number\Number;
use LogicException;

final class Segment
{
    private function __construct(
        private readonly Point $start,
        private readonly Point $end,
    ) {
    }

    public static function between(Point $start, Point $end): self
    {
        return new self($start, $end);
    }

    public function start(): Point
    {
        return $this->start;
    }

    public function end(): Point
    {
        return $this->end;
    }

    public function offPrecision(): self
    {
        return new self($this->start->offPrecision(), $this->end->offPrecision());
    }

    /**
     * Returns the Euclidean length; the Number domain must support its sqrt.
     */
    public function length(): Number
    {
        return $this->start->distanceTo($this->end);
    }

    public function direction(): Vector
    {
        return $this->start->vectorTo($this->end);
    }

    public function midpoint(): Point
    {
        return $this->start->midpoint($this->end);
    }

    public function contains(Point $point): bool
    {
        $direction = $this->direction();
        if ($direction->isZero()) {
            return $point->equals($this->start);
        }

        $relative = $point->coordinates()->sub($this->start->coordinates());
        if (! $relative->isParallelTo($direction)) {
            return false;
        }

        $dot = $relative->dot($direction);
        $lengthSquared = $direction->dot($direction);
        $zero = Number::of(0);
        $one = Number::of(1);

        if ($lengthSquared->compare($zero) === 0) {
            throw new LogicException('Segment direction has zero self-dot-product; containment is undefined.');
        }

        $parameter = $dot->div($lengthSquared);

        return $parameter->compare($zero) >= 0 && $parameter->compare($one) <= 0;
    }
}
