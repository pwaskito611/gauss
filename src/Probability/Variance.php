<?php

declare(strict_types=1);

namespace Gauss\Probability;

use Gauss\Number\Number;

final class Variance
{
    private function __construct(
        private readonly RandomVariable $variable,
        private readonly ProbabilityMeasure $measure,
    ) {
        if (! $this->variable->sampleSpace()->equals($this->measure->sampleSpace())) {
            throw new \InvalidArgumentException('RandomVariable and ProbabilityMeasure must use the same sample space.');
        }
    }

    public static function of(RandomVariable $variable, ProbabilityMeasure $measure): self
    {
        return new self($variable, $measure);
    }

    public function value(): Number
    {
        $mean = Expectation::of($this->variable, $this->measure)->value();
        $sum = Number::of(0);

        foreach ($this->variable->sampleSpace()->outcomes() as $outcome) {
            $probability = $this->measure->probabilityFor($outcome);
            $deviation = $this->variable->valueFor($outcome)->sub($mean);
            $sum = $sum->add($deviation->pow(2)->mul($probability->value()));
        }

        return $sum;
    }
}
