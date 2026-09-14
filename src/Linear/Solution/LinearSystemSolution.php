<?php

declare(strict_types=1);

namespace Gauss\Linear\Solution;

interface LinearSystemSolution
{
    public function hasSolution(): bool;
}
