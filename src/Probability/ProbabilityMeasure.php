<?php

declare(strict_types=1);

namespace Gauss\Probability;

use DivisionByZeroError;
use Gauss\Number\Number;
use InvalidArgumentException;

final class ProbabilityMeasure
{
    private readonly SampleSpace $space;

    /** @var array<string, Probability> */
    private readonly array $weights;

    /** @param array<mixed, Probability|string|int|float|Number|array{0:mixed,1:Probability|string|int|float|Number}> $weights */
    private function __construct(SampleSpace $space, array $weights, bool $explicitMap = false)
    {
        $probabilities = [];
        $outcomes = $space->outcomes();
        if (! $explicitMap) {
            if (! array_is_list($weights) || count($weights) !== count($outcomes)) {
                throw new InvalidArgumentException('ProbabilityMeasure::of() requires one positional weight per outcome.');
            }

            $entries = [];
            foreach ($outcomes as $index => $outcome) {
                $entries[] = [$outcome, $weights[$index]];
            }
        } else {
            $entries = [];
            $pairMap = array_is_list($weights) && $weights !== []
                && is_array($weights[0]) && count($weights[0]) === 2;
            foreach ($weights as $candidate => $weight) {
                if ($pairMap) {
                    if (! is_array($weight) || count($weight) !== 2) {
                        throw new InvalidArgumentException('Mapped weights must contain outcome-weight pairs.');
                    }
                    $entries[] = [$weight[0], $weight[1]];
                } else {
                    $entries[] = [$candidate, $weight];
                }
            }
        }

        $sourceIdentities = [];
        $entriesByIdentity = [];
        foreach ($entries as [$candidate, $weight]) {
            $identity = OutcomeIdentity::key($candidate);
            if (isset($sourceIdentities[$identity])) {
                throw new InvalidArgumentException('Each sample-space outcome may have only one probability weight.');
            }
            $sourceIdentities[$identity] = true;
            $entriesByIdentity[$identity] = $weight;
        }

        foreach ($outcomes as $outcome) {
            $outcomeIdentity = OutcomeIdentity::key($outcome);
            if (! array_key_exists($outcomeIdentity, $entriesByIdentity)) {
                throw new InvalidArgumentException('Each sample-space outcome must have a probability weight.');
            }
            $probabilities[$outcomeIdentity] = Probability::of($entriesByIdentity[$outcomeIdentity]);
        }

        if (count($sourceIdentities) !== count($outcomes)) {
            throw new InvalidArgumentException('Probability weights must correspond to the sample space.');
        }

        $sample = reset($probabilities)->value();
        $sum = $sample->sub($sample);
        $one = $sample->one();
        foreach ($probabilities as $probability) {
            $sum = $sum->add($probability->value());
        }

        if ($sum->compare($one) !== 0) {
            throw new InvalidArgumentException('Probability weights for a sample space must sum to exactly 1.');
        }

        $this->space = $space;
        $this->weights = $probabilities;
    }

    /** @param list<Probability|string|int|float|Number> $weights */
    public static function of(SampleSpace $space, array $weights): self
    {
        return new self($space, $weights);
    }

    /**
     * Creates a measure from explicit outcome-weight pairs or a scalar-keyed map.
     * Pair form supports outcomes that cannot be PHP array keys, such as objects.
     *
     * @param array<mixed, Probability|string|int|float|Number|array{0:mixed,1:Probability|string|int|float|Number}> $weights
     */
    public static function fromMap(SampleSpace $space, array $weights): self
    {
        return new self($space, $weights, true);
    }

    public static function uniform(SampleSpace $space): self
    {
        $count = Number::of($space->size());
        $sample = $count->one();
        $unitWeight = $sample->div($count);
        $weights = array_fill(0, $space->size(), $unitWeight);
        $sum = $unitWeight->sub($unitWeight);
        for ($index = 0; $index < $space->size() - 1; $index++) {
            $sum = $sum->add($unitWeight);
        }
        $weights[$space->size() - 1] = $sample->sub($sum);

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
            return Probability::of(Number::of(0));
        }

        $sum = Number::of(0);
        foreach ($event->outcomes() as $outcome) {
            $identity = OutcomeIdentity::key($outcome);
            $sum = $sum->add($this->weights[$identity]->value());
        }

        return Probability::of($sum);
    }

    public function probabilityFor(mixed $outcome): Probability
    {
        if (is_float($outcome) && is_nan($outcome)) {
            throw new InvalidArgumentException('Outcome is not in the sample space.');
        }

        $identity = OutcomeIdentity::key($outcome);
        if (! isset($this->weights[$identity])) {
            throw new InvalidArgumentException('Outcome is not in the sample space.');
        }

        return $this->weights[$identity];
    }

    public function conditional(Event $a, Event $b): Probability
    {
        $this->assertSameSampleSpace($a, $b);

        $denominator = $this->probabilityOf($b);
        if ($denominator->isZero()) {
            throw new DivisionByZeroError('Conditional probability is undefined when P(B) = 0.');
        }

        $numerator = $this->probabilityOf($a->intersection($b));
        $ratio = $numerator->value()->div($denominator->value());

        return Probability::of($ratio);
    }

    public function areIndependent(Event $a, Event $b): bool
    {
        $this->assertSameSampleSpace($a, $b);

        $jointProbability = $this->probabilityOf($a->intersection($b));
        $left = $this->probabilityOf($a);
        $right = $this->probabilityOf($b);
        $product = $left->value()->mul($right->value());

        return $jointProbability->value()->compare($product) === 0;
    }

    private function assertSameSampleSpace(Event ...$events): void
    {
        foreach ($events as $event) {
            if (! $this->space->equals($event->sampleSpace())) {
                throw new InvalidArgumentException('Event must belong to the same sample space.');
            }
        }
    }
}
