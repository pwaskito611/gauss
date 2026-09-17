<?php

declare(strict_types=1);

namespace Gauss\Distribution;

use Gauss\Number\Number;
use Gauss\Probability\Probability;

interface DiscreteDistribution extends Distribution
{
    public function pmf(int|float|string|Number $x): Probability;
    public function cdf(int|float|string|Number $x): Probability;
}
