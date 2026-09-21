<?php

declare(strict_types=1);

namespace Gauss\Probability;

use DivisionByZeroError;
use Gauss\Number\Number;
use InvalidArgumentException;

final class ProbabilityMeasure
{
    private readonly SampleSpace $space;

    /** @var array<mixed, Probability> */
    private readonly array $weights;

    /** @param array<mixed, Probability|string|int|float> $weights */
    private function __construct(SampleSpace $space, array $weights)
    {
        foreach ($space->outcomes() as $outcome) {
            if (! array_key_exists($outcome, $weights)) {
                throw new InvalidArgumentException('Each sample-space outcome must have a probability weight.');
            }
        }

        $probabilities = [];
        foreach ($weights as $outcome => $probability) {
            if (! $space->contains($outcome)) {
                throw new InvalidArgumentException('Probability weights must correspond to the sample space.');
            }
            $probabilities[$outcome] = Probability::of($probability);
        }

        $sum = Number::of(0);
        foreach ($probabilities as $probability) {
            $sum = $sum->add($probability->value());
        }

        if ($sum->sub(1)->abs()->compare('0.000000000001') > 0) {
            throw new InvalidArgumentException('Probability weights for a sample space must sum to 1.');
        }

        $this->space = $space;
        $this->weights = $probabilities;
    }

    public static function of(SampleSpace $space, array $weights): self
    {
        return new self($space, $weights);
    }

    public static function uniform(SampleSpace $space): self
    {
        $weights = [];
        $count = Number::of($space->size());
        foreach ($space->outcomes() as $outcome) {
            $weights[$outcome] = Number::of(1)->div($count);
        }

        return new self($space, $weights);
    }

    public function sampleSpace(): SampleSpace
    {
        return $this->space;
    }

    public function probabilityOf(Event $event): Probability
    {
        $this->assertSameSampleSpace($event);

        if ($event->isEmpty()) {
            return Probability::of(0);
        }

        $sum = Number::of(0);
        foreach ($event->outcomes() as $outcome) {
            $sum = $sum->add($this->weights[$outcome]->value());
        }

        return Probability::of($sum);
    }

    public function conditional(Event $a, Event $b): Probability
    {
        $this->assertSameSampleSpace($a, $b);

        $denominator = $this->probabilityOf($b);
        if ($denominator->isZero()) {
            throw new DivisionByZeroError('Conditional probability is undefined when P(B) = 0.');
        }

        $numerator = $this->probabilityOf($a->intersection($b));
        $ratio = Number::of($numerator->value())->div(Number::of($denominator->value()));

        return Probability::of($ratio);
    }

    public function areIndependent(Event $a, Event $b): bool
    {
        $this->assertSameSampleSpace($a, $b);

        $jointProbability = $this->probabilityOf($a->intersection($b));
        $left = $this->probabilityOf($a);
        $right = $this->probabilityOf($b);
        $product = Number::of($left->value())->mul(Number::of($right->value()));

        return Number::of($jointProbability->value())->compare($product) === 0;
    }

    private function assertSameSampleSpace(Event ...$events): void
    {
        foreach ($events as $event) {
            if ($event->sampleSpace()->size() !== $this->space->size()) {
                throw new InvalidArgumentException('Event must belong to the same sample space.');
            }

            foreach ($this->space->outcomes() as $index => $outcome) {
                if ($outcome !== $event->sampleSpace()->outcomes()[$index]) {
                    throw new InvalidArgumentException('Event must belong to the same sample space.');
                }
            }
        }
    }
}
