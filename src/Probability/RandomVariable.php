<?php

declare(strict_types=1);

namespace Gauss\Probability;

use Gauss\Number\Number;
use InvalidArgumentException;

final class RandomVariable
{
    /** @var list<Number> */
    private readonly array $mapping;
    /** @var array<string, Number> */
    private readonly array $lookup;

    private readonly SampleSpace $space;

    /** @param list<int|float|string|Number> $mapping */
    private function __construct(SampleSpace $space, array $mapping, bool $explicitMap = false)
    {
        $normalized = [];
        $lookup = [];
        $outcomes = $space->outcomes();
        if (! $explicitMap) {
            if (! array_is_list($mapping) || count($mapping) !== count($outcomes)) {
                throw new InvalidArgumentException('RandomVariable::of() requires one positional value per outcome.');
            }

            foreach ($outcomes as $index => $outcome) {
                $value = Number::of($mapping[$index]);
                $normalized[] = $value;
                $lookup[OutcomeIdentity::key($outcome)] = $value;
            }
        } else {
            $entries = [];
            $pairMap = $explicitMap && array_is_list($mapping) && $mapping !== []
                && is_array($mapping[0]) && count($mapping[0]) === 2;
            foreach ($mapping as $candidate => $value) {
                if ($pairMap) {
                    if (! is_array($value) || count($value) !== 2) {
                        throw new InvalidArgumentException('Mapped values must contain outcome-value pairs.');
                    }
                    $entries[] = [$value[0], $value[1]];
                } else {
                    $entries[] = [$candidate, $value];
                }
            }

            $sourceIdentities = [];
            foreach ($entries as [$candidate]) {
                $identity = OutcomeIdentity::key($candidate);
                if (isset($sourceIdentities[$identity])) {
                    throw new InvalidArgumentException('Random variable mapping cannot define duplicate source outcomes.');
                }
                $sourceIdentities[$identity] = true;
            }

            foreach ($outcomes as $outcome) {
                $found = false;
                $outcomeIdentity = OutcomeIdentity::key($outcome);
                foreach ($entries as [$candidate, $value]) {
                    if (OutcomeIdentity::key($candidate) === $outcomeIdentity) {
                        $normalized[] = Number::of($value);
                        $lookup[$outcomeIdentity] = $normalized[array_key_last($normalized)];
                        $found = true;
                        break;
                    }
                }
                if (! $found) {
                    throw new InvalidArgumentException('Random variable must define a numeric value for every sample outcome.');
                }
            }

            foreach ($entries as [$candidate]) {
                if (! $space->contains($candidate)) {
                    throw new InvalidArgumentException('Random variable mapping must use sample space outcomes only.');
                }
            }
        }

        $this->space = $space;
        $this->mapping = $normalized;
        $this->lookup = $lookup;
    }

    /** @param list<int|float|string|Number> $mapping */
    public static function of(SampleSpace $space, array $mapping): self
    {
        return new self($space, $mapping);
    }

    /** @param array<mixed, int|float|string|Number|array{0:mixed,1:int|float|string|Number}> $mapping */
    public static function fromMap(SampleSpace $space, array $mapping): self
    {
        return new self($space, $mapping, true);
    }

    public function sampleSpace(): SampleSpace
    {
        return $this->space;
    }

    /** @return list<Number> */
    public function mapping(): array
    {
        return $this->mapping;
    }

    public function valueFor(mixed $outcome): Number
    {
        if (! $this->space->contains($outcome)) {
            throw new InvalidArgumentException('Outcome is not in the sample space.');
        }

        return $this->lookup[OutcomeIdentity::key($outcome)];
    }
}
