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
            $probability = $this->measure->probabilityOf(Event::of($this->variable->sampleSpace(), $outcome));
            $deviation = $this->variable->valueFor($outcome)->sub($mean);
            $sum = $sum->add($deviation->pow(2)->mul($probability->value()));
        }

        return $sum;
    }
}
