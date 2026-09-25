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
    public static function of(...$outcomes): self
    {
        return new self($outcomes);
    }

    /** @return list<mixed> */
    public function outcomes(): array
    {
        return $this->outcomes;
    }

    /** @return list<mixed> */
    public function values(): array
    {
        return $this->outcomes;
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

    public function size(): int
    {
        return count($this->outcomes);
    }
}
