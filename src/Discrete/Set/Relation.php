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
}
