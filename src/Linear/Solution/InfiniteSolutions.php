<?php

declare(strict_types=1);

namespace Gauss\Linear\Solution;

use Gauss\Linear\Matrix;

final class InfiniteSolutions implements LinearSystemSolution
{
    public function __construct(
        private readonly Matrix $reducedMatrix,
        private readonly array $freeColumns,
    ) {
    }

    public function hasSolution(): bool
    {
        return true;
    }

    public function reducedMatrix(): Matrix
    {
        return $this->reducedMatrix;
    }

    /** @return list<int> */
    public function freeColumns(): array
    {
        return $this->freeColumns;
    }
}
