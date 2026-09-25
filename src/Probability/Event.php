<?php

declare(strict_types=1);

namespace Gauss\Probability;

use InvalidArgumentException;

final class Event
{
    /** @var list<mixed> */
    private readonly array $outcomes;

    private readonly SampleSpace $space;

    /** @param list<mixed> $outcomes */
    private function __construct(SampleSpace $space, array $outcomes)
    {
        foreach ($outcomes as $outcome) {
            if (! $space->contains($outcome)) {
                throw new InvalidArgumentException('Event outcome must belong to the sample space.');
            }
        }

        $this->space = $space;
        $unique = [];
        $identities = [];
        foreach ($outcomes as $outcome) {
            $identity = OutcomeIdentity::key($outcome);
            if (! isset($identities[$identity])) {
                $identities[$identity] = true;
                $unique[] = $outcome;
            }
        }
        $this->outcomes = $unique;
    }

    /** @param mixed ...$outcomes */
    public static function of(SampleSpace $space, ...$outcomes): self
    {
        return new self($space, $outcomes);
    }

    public static function all(SampleSpace $space): self
    {
        return new self($space, $space->outcomes());
    }

    public static function empty(SampleSpace $space): self
    {
        return new self($space, []);
    }

    /** @return list<mixed> */
    public function outcomes(): array
    {
        return $this->outcomes;
    }

    public function sampleSpace(): SampleSpace
    {
        return $this->space;
    }

    public function contains(mixed $outcome): bool
    {
        if (is_float($outcome) && is_nan($outcome)) {
            return false;
        }

        $identity = OutcomeIdentity::key($outcome);
        foreach ($this->outcomes as $item) {
            if (OutcomeIdentity::key($item) === $identity) {
                return true;
            }
        }

        return false;
    }

    public function equals(self $other): bool
    {
        if (! $this->sameSampleSpace($other)) {
            return false;
        }

        return count($this->outcomes) === count($other->outcomes)
            && $this->containsAll($other->outcomes)
            && $other->containsAll($this->outcomes);
    }

    public function union(self $other): self
    {
        $this->assertSameSampleSpace($other);

        return new self($this->space, array_merge($this->outcomes, $other->outcomes));
    }

    public function intersection(self $other): self
    {
        $this->assertSameSampleSpace($other);

        $items = [];
        foreach ($this->outcomes as $item) {
            if ($other->contains($item)) {
                $items[] = $item;
            }
        }

        return new self($this->space, $items);
    }

    public function difference(self $other): self
    {
        $this->assertSameSampleSpace($other);

        $items = [];
        foreach ($this->outcomes as $item) {
            if (! $other->contains($item)) {
                $items[] = $item;
            }
        }

        return new self($this->space, $items);
    }

    public function complement(): self
    {
        $items = [];
        foreach ($this->space->outcomes() as $outcome) {
            if (! $this->contains($outcome)) {
                $items[] = $outcome;
            }
        }

        return new self($this->space, $items);
    }

    public function isEmpty(): bool
    {
        return $this->outcomes === [];
    }

    private function assertSameSampleSpace(self $other): void
    {
        if (! $this->sameSampleSpace($other)) {
            throw new InvalidArgumentException('Events must share the same sample space.');
        }
    }

    private function sameSampleSpace(self $other): bool
    {
        return $this->space->size() === $other->space->size()
            && $this->containsSampleSpaceOutcomes($other->space)
            && $other->containsSampleSpaceOutcomes($this->space);
    }

    /** @param list<mixed> $outcomes */
    private function containsAll(array $outcomes): bool
    {
        foreach ($outcomes as $outcome) {
            if (! $this->contains($outcome)) {
                return false;
            }
        }

        return true;
    }

    private function containsSampleSpaceOutcomes(SampleSpace $space): bool
    {
        foreach ($space->outcomes() as $outcome) {
            if (! $this->space->contains($outcome)) {
                return false;
            }
        }

        return true;
    }
}
