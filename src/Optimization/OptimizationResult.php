<?php

declare(strict_types=1);

namespace Gauss\Optimization;

use Gauss\Linear\Vector;
use Gauss\Number\Number;

final class OptimizationResult
{
    public function __construct(
        private readonly Number|Vector $point,
        private readonly Number $value,
        private readonly int $iterations,
        private readonly bool $converged,
    ) {
    }

    public function point(): Number|Vector
    {
        return $this->point;
    }

    public function value(): Number
    {
        return $this->value;
    }

    public function iterations(): int
    {
        return $this->iterations;
    }

    public function converged(): bool
    {
        return $this->converged;
    }
}
