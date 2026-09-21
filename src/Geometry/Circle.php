<?php

declare(strict_types=1);

namespace Gauss\Geometry;

use Gauss\Number\Number;
use Gauss\Number\NumericValue;
use InvalidArgumentException;

final class Circle
{
    private function __construct(
        private readonly Point $center,
        private readonly Number $radius,
    ) {
        if ($radius->compare(0) < 0) {
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

    public function area(): NumericValue
    {
        return $this->radius->pow(2)->mul(Number::pi());
    }

    public function circumference(): NumericValue
    {
        return $this->radius->mul(2)->mul(Number::pi());
    }

    public function contains(Point $point): bool
    {
        return $this->center->distanceTo($point)->compare($this->radius) <= 0;
    }
}
