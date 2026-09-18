<?php

declare(strict_types=1);

namespace Gauss\Discrete\Set;

use Gauss\Number\Number;

final class Relation
{
    /** @var list<array{0:Number,1:Number}> */
    private readonly array $pairs;

    /**
     * @param list<array{0:Number,1:Number}> $pairs
     */
    private function __construct(array $pairs)
    {
        $this->pairs = $pairs;
    }

    /**
     * @param list<array{0:Number,1:Number}> $pairs
     */
    public static function of(array $pairs): self
    {
        return new self($pairs);
    }

    /** @param array{0:Number,1:Number} $pair */
    public function contains(array $pair): bool
    {
        foreach ($this->pairs as $existing) {
            if ($existing[0]->compare($pair[0]) === 0 && $existing[1]->compare($pair[1]) === 0) {
                return true;
            }
        }

        return false;
    }

    /** @return list<Number> */
    public function domain(): array
    {
        $domain = [];
        foreach ($this->pairs as [$left, $right]) {
            if (! $this->containsElement($domain, $left)) {
                $domain[] = $left;
            }
        }

        return $domain;
    }

    /** @return list<Number> */
    public function range(): array
    {
        $range = [];
        foreach ($this->pairs as [$left, $right]) {
            if (! $this->containsElement($range, $right)) {
                $range[] = $right;
            }
        }

        return $range;
    }

    public function inverse(): self
    {
        $pairs = [];
        foreach ($this->pairs as [$left, $right]) {
            $pairs[] = [$right, $left];
        }

        return self::of($pairs);
    }

    public function compose(self $other): self
    {
        $pairs = [];
        foreach ($this->pairs as [$left, $middle]) {
            foreach ($other->pairs as [$otherLeft, $otherRight]) {
                if ($middle->compare($otherLeft) === 0) {
                    $pairs[] = [$left, $otherRight];
                }
            }
        }

        return self::of($pairs);
    }

    private function containsElement(array $values, Number $needle): bool
    {
        foreach ($values as $value) {
            if ($value->compare($needle) === 0) {
                return true;
            }
        }

        return false;
    }
}
