<?php

declare(strict_types=1);

namespace Gauss\Linear\Solution;

final class NoSolution implements LinearSystemSolution
{
    public function hasSolution(): bool
    {
        return false;
    }
}
