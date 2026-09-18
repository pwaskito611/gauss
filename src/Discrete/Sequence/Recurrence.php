<?php

declare(strict_types=1);

namespace Gauss\Discrete\Sequence;

use Closure;
use Gauss\Number\Number;
use InvalidArgumentException;

final class Recurrence
{
    /** @var list<Number> */
    private readonly array $initialValues;

    /** @var Closure */
    private readonly mixed $rule;

    /**
     * @param list<Number> $initialValues
     * @param callable(Number, Number): Number $rule
     */
    private function __construct(array $initialValues, callable $rule)
    {
        if ($initialValues === []) {
            throw new InvalidArgumentException('Recurrence needs at least one initial value.');
        }

        $this->initialValues = $initialValues;
        $this->rule = $rule;
    }

    /**
     * @param list<Number> $initialValues
     * @param callable(Number, Number): Number $rule
     */
    public static function of(array $initialValues, callable $rule): self
    {
        return new self($initialValues, $rule);
    }

    public function first(): Number
    {
        return $this->initialValues[0];
    }

    public function at(Number $n): Number
    {
        if ($n->compare(Number::of(1)) < 0) {
            throw new InvalidArgumentException('Sequence index must be at least 1.');
        }

        if (count($this->initialValues) < 2) {
            throw new InvalidArgumentException('Recurrence requires at least two initial values.');
        }

        if ($n->compare(Number::of(count($this->initialValues))) <= 0) {
            return $this->initialValues[(int) $n->value() - 1];
        }

        $values = $this->initialValues;
        $index = Number::of(count($values));
        while ($index->compare($n) < 0) {
            $next = ($this->rule)(
                $values[count($values) - 1],
                $values[count($values) - 2]
            );
            $values[] = $next;
            $index = $index->add(1);
        }

        return $values[(int) $n->value() - 1];
    }
}
