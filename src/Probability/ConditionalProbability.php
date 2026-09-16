<?php

declare(strict_types=1);

namespace Gauss\Probability;

use DivisionByZeroError;

final class ConditionalProbability
{
    private function __construct(
        private readonly Event $eventA,
        private readonly Event $eventB,
        private readonly ProbabilityMeasure $measure,
    ) {
    }

    public static function of(Event $eventA, Event $eventB, ProbabilityMeasure $measure): self
    {
        $measure->conditional($eventA, $eventB);

        return new self($eventA, $eventB, $measure);
    }

    public function value(): Probability
    {
        return $this->measure->conditional($this->eventA, $this->eventB);
    }
}
