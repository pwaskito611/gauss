<?php

declare(strict_types=1);

namespace Gauss\Discrete\Sequence;

use Gauss\Number\Number;

final class ArithmeticSequence
{
    private function __construct(
        private readonly Number $first,
        private readonly Number $difference,
    ) {
    }

    public static function from(Number $first, Number $difference): self
    {
        return new self($first, $difference);
    }

    public function first(): Number
    {
        return $this->first;
    }

    public function at(Number $n): Number
    {
        $offset = $n->sub(Number::of(1));
        return $this->first->add($offset->mul($this->difference));
    }
}
