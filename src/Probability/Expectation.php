<?php

declare(strict_types=1);

namespace Gauss\Probability;

use Gauss\Number\Number;

final class Expectation
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
        $sum = Number::of(0);

        foreach ($this->variable->sampleSpace()->outcomes() as $outcome) {
            $probability = $this->measure->probabilityOf(Event::of($this->variable->sampleSpace(), $outcome));
            $sum = $sum->add($this->variable->valueFor($outcome)->mul($probability->value()));
        }

        return $sum;
    }
}
