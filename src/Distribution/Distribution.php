<?php

declare(strict_types=1);

namespace Gauss\Distribution;

use Gauss\Number\Number;

interface Distribution
{
    public function expectation(): Number;
    public function variance(): Number;
}
