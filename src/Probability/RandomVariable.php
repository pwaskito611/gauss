<?php

declare(strict_types=1);

namespace Gauss\Probability;

use Gauss\Number\Number;
use InvalidArgumentException;

final class RandomVariable
{
    /** @var array<mixed, Number> */
    private readonly array $mapping;

    private readonly SampleSpace $space;

    /** @param array<mixed, int|float|string|Number> $mapping */
    private function __construct(SampleSpace $space, array $mapping)
    {
        foreach ($space->outcomes() as $outcome) {
            if (! array_key_exists($outcome, $mapping)) {
                throw new InvalidArgumentException('Random variable must define a numeric value for every sample outcome.');
            }
        }

        $normalized = [];
        foreach ($mapping as $outcome => $value) {
            if (! $space->contains($outcome)) {
                throw new InvalidArgumentException('Random variable mapping must use sample space outcomes only.');
            }
            $normalized[$outcome] = Number::of($value);
        }

        $this->space = $space;
        $this->mapping = $normalized;
    }

    /** @param array<mixed, int|float|string|Number> $mapping */
    public static function of(SampleSpace $space, array $mapping): self
    {
        return new self($space, $mapping);
    }

    public function sampleSpace(): SampleSpace
    {
        return $this->space;
    }

    /** @return array<mixed, Number> */
    public function mapping(): array
    {
        return $this->mapping;
    }

    public function valueFor(mixed $outcome): Number
    {
        if (! $this->space->contains($outcome)) {
            throw new InvalidArgumentException('Outcome is not in the sample space.');
        }

        return $this->mapping[$outcome];
    }
}
