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
        $this->outcomes = array_values(array_unique($outcomes, SORT_REGULAR));
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
        foreach ($this->outcomes as $item) {
            if ($item === $outcome) {
                return true;
            }
        }

        return false;
    }

    public function equals(self $other): bool
    {
        if ($this->space->size() !== $other->space->size()) {
            return false;
        }

        $thisSet = $this->outcomes;
        $otherSet = $other->outcomes;

        sort($thisSet);
        sort($otherSet);

        return $thisSet === $otherSet;
    }

    public function union(self $other): self
    {
        $this->assertSameSampleSpace($other);

        return new self($this->space, array_values(array_unique(array_merge($this->outcomes, $other->outcomes), SORT_REGULAR)));
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
        if ($this->space->size() !== $other->space->size()) {
            throw new InvalidArgumentException('Events must share the same sample space.');
        }

        foreach ($this->space->outcomes() as $index => $outcome) {
            if ($outcome !== $other->space->outcomes()[$index]) {
                throw new InvalidArgumentException('Events must share the same sample space.');
            }
        }
    }
}
