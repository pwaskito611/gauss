<?php

declare(strict_types=1);

namespace Gauss\Geometry;

use Gauss\Linear\Vector;
use Gauss\Number\Number;
use InvalidArgumentException;
use LogicException;

final class Line
{
    private function __construct(
        private readonly Point $point,
        private readonly Vector $direction,
    ) {
        if ($direction->isZero()) {
            throw new InvalidArgumentException('Line direction cannot be the zero vector.');
        }
    }

    public static function through(Point $point, Vector $direction): self
    {
        return new self($point, $direction);
    }

    public function point(): Point
    {
        return $this->point;
    }

    public function direction(): Vector
    {
        return $this->direction;
    }

    public function contains(Point $point): bool
    {
        return $point->coordinates()->sub($this->point->coordinates())->isParallelTo($this->direction);
    }

    /**
     * Projects a point using the direction self-dot-product as denominator.
     * Projection is undefined when that denominator is zero.
     */
    public function project(Point $point): Point
    {
        $relative = $point->coordinates()->sub($this->point->coordinates());
        $denominator = $this->direction->dot($this->direction);
        $zero = Number::of(0);

        if ($denominator->compare($zero) === 0) {
            throw new LogicException('Line direction has zero self-dot-product; projection is undefined.');
        }

        $scalar = $relative->dot($this->direction)->div($denominator);

        return $this->point->translate(
            $this->direction->scale($scalar)
        );
    }

    /**
     * Returns the distance via projection and requires the Number domain's sqrt.
     */
    public function distanceTo(Point $point): Number
    {
        $projected = $this->project($point);

        return $projected->coordinates()->sub($point->coordinates())->norm();
    }

    public function intersectionWith(self $other): Point
    {
        if ($this->direction->dimension() !== 2 || $other->direction->dimension() !== 2) {
            throw new InvalidArgumentException('Line intersection is defined for 2D lines.');
        }

        $determinant = self::determinant2D($this->direction, $other->direction);
        if ($determinant->compare(Number::of(0)) === 0) {
            throw new LogicException('Lines are parallel (or coincident) and do not intersect at a unique point.');
        }

        $offset = $other->point->coordinates()->sub($this->point->coordinates());
        $numerator = self::determinant2D($offset, $other->direction);
        $scalar = $numerator->div($determinant);

        return $this->point->translate(
            $this->direction->scale($scalar)
        );
    }

    private static function determinant2D(Vector $left, Vector $right): Number
    {
        return $left->get(0)->mul($right->get(1))->sub(
            $left->get(1)->mul($right->get(0))
        );
    }
}
