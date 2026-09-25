<?php

declare(strict_types=1);

namespace Gauss\Probability;

final class ConditionalProbability
{
    private function __construct(
        private readonly Probability $result,
    ) {
    }

    public static function of(Event $eventA, Event $eventB, ProbabilityMeasure $measure): self
    {
        return new self($measure->conditional($eventA, $eventB));
    }

    public function value(): Probability
    {
        return $this->result;
    }
}
