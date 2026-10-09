<?php

declare(strict_types=1);

namespace Gauss\Linear\Solution;

use Gauss\Linear\Vector;

final class UniqueSolution implements LinearSystemSolution
{
    public function __construct(private readonly Vector $vector)
    {
    }

    public function hasSolution(): bool
    {
        return true;
    }

    public function vector(): Vector
    {
        return $this->vector;
    }

    public function offPrecision(): self
    {
        return new self($this->vector->offPrecision());
    }
}
