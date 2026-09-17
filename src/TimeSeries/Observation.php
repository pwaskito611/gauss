<?php

declare(strict_types=1);

namespace Gauss\TimeSeries;

use Gauss\Number\Number;
use InvalidArgumentException;

final class Observation
{
    public function __construct(
        private readonly int $index,
        private readonly Number $value,
    ) {
    }

    public function index(): int
    {
        return $this->index;
    }

    public function value(): Number
    {
        return $this->value;
    }
}
