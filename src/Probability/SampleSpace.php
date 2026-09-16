<?php

declare(strict_types=1);

namespace Gauss\Probability;

use InvalidArgumentException;

final class SampleSpace
{
    /** @var list<mixed> */
    private readonly array $outcomes;

    /** @param list<mixed> $outcomes */
    private function __construct(array $outcomes)
    {
        if ($outcomes === []) {
            throw new InvalidArgumentException('Sample space cannot be empty.');
        }

        $this->outcomes = array_values(array_unique($outcomes, SORT_REGULAR));
    }

    /** @param mixed ...$outcomes */
    public static function of(...$outcomes): self
    {
        return new self($outcomes);
    }

    /** @return list<mixed> */
    public function outcomes(): array
    {
        return $this->outcomes;
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

    public function size(): int
    {
        return count($this->outcomes);
    }
}
