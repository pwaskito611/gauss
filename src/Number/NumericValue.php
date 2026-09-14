<?php 

declare(strict_types=1);

namespace Gauss\Number;

interface NumericValue
{
    public function add(NumericValue $other): NumericValue;
    public function sub(NumericValue $other): NumericValue;
    public function mul(NumericValue $other): NumericValue;
    public function div(NumericValue $other): NumericValue;
    public function value(): string;
}
