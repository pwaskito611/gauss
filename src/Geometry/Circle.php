<?php

declare(strict_types=1);

namespace Gauss\Geometry;

use Gauss\Number\Number;
use InvalidArgumentException;

/**
 * A 2D circle over the Number domain.
 *
 * Area and circumference depend on Number::pi(). Distance-based containment
 * depends on the domain being able to represent the required square root.
 */
final class Circle
{
    private function __construct(
        private readonly Point $center,
        private readonly Number $radius,
    ) {
        if ($radius->compare($radius->sub($radius)) < 0) {
            throw new InvalidArgumentException('Circle radius must be non-negative.');
        }
    }

    public static function of(Point $center, int|float|string|Number $radius): self
    {
        return new self($center, Number::of($radius));
    }

    public function center(): Point
    {
        return $this->center;
    }

    public function radius(): Number
    {
        return $this->radius;
    }

    /**
     * Returns pi times the squared radius, using Number::pi()'s representation.
     */
    public function area(): Number
    {
        return $this->radius->pow(2)->mul(Number::pi());
    }

    /**
     * Returns two pi times the radius, using Number::pi()'s representation.
     */
    public function circumference(): Number
    {
        $two = $this->radius->one()->add($this->radius->one());

        return $this->radius->mul($two)->mul(Number::pi());
    }

    /**
     * Tests containment inclusively; points on the boundary are contained.
     * The distance calculation requires the Number domain's sqrt.
     */
    public function contains(Point $point): bool
    {
        return $this->center->distanceTo($point)->compare($this->radius) <= 0;
    }
}
