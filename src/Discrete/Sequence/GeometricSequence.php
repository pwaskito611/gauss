<?php

declare(strict_types=1);

namespace Gauss\Discrete\Sequence;

use Gauss\Number\Number;

final class GeometricSequence
{
    private function __construct(
        private readonly Number $first,
        private readonly Number $ratio,
    ) {
    }

    public static function from(Number $first, Number $ratio): self
    {
        return new self($first, $ratio);
    }

    public function first(): Number
    {
        return $this->first;
    }

    public function at(Number $n): Number
    {
        if ($n->compare(Number::of(1)) < 0) {
            throw new \InvalidArgumentException('Sequence index must be at least 1.');
        }

        $offset = $n->sub(Number::of(1));
        return $this->first->mul($this->ratio->pow((int) $offset->value()));
    }
}
