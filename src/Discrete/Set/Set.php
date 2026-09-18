<?php

declare(strict_types=1);

namespace Gauss\Discrete\Set;

use Gauss\Number\Number;
use InvalidArgumentException;

final class Set
{
    /** @var list<Number> */
    private readonly array $values;

    /** @param list<Number> $values */
    private function __construct(array $values)
    {
        $unique = [];
        foreach ($values as $value) {
            $exists = false;
            foreach ($unique as $item) {
                if ($item->compare($value) === 0) {
                    $exists = true;
                    break;
                }
            }

            if (! $exists) {
                $unique[] = $value;
            }
        }

        $this->values = $unique;
    }

    /** @param list<Number> $values */
    public static function of(array $values): self
    {
        return new self($values);
    }

    public function contains(Number $value): bool
    {
        foreach ($this->values as $item) {
            if ($item->compare($value) === 0) {
                return true;
            }
        }

        return false;
    }

    public function count(): int
    {
        return count($this->values);
    }

    public function union(self $other): self
    {
        $merged = $this->values;
        foreach ($other->values as $value) {
            if (! $this->contains($value)) {
                $merged[] = $value;
            }
        }

        return new self($merged);
    }

    public function intersection(self $other): self
    {
        $values = [];
        foreach ($this->values as $value) {
            if ($other->contains($value)) {
                $values[] = $value;
            }
        }

        return new self($values);
    }

    public function difference(self $other): self
    {
        $values = [];
        foreach ($this->values as $value) {
            if (! $other->contains($value)) {
                $values[] = $value;
            }
        }

        return new self($values);
    }
}
