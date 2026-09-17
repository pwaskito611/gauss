<?php

declare(strict_types=1);

namespace Gauss\Distribution;

use Gauss\Number\Number;
use Gauss\Probability\Probability;

interface ContinuousDistribution extends Distribution
{
    public function pdf(int|float|string|Number $x): Number;
    public function cdf(int|float|string|Number $x): Probability;
}
