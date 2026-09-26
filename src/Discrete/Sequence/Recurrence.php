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
        if (count($initialValues) !== 2) {
            throw new InvalidArgumentException('Second-order recurrence requires exactly two initial values.');
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
        if (! preg_match('/^\d+$/', $n->value())) {
            throw new InvalidArgumentException('Sequence index must be an integer.');
        }

        if ($n->compare(Number::of(1)) < 0) {
            throw new InvalidArgumentException('Sequence index must be at least 1.');
        }

        if ($n->compare(Number::of(2)) <= 0) {
            return $this->initialValues[$n->compare(Number::of(1)) === 0 ? 0 : 1];
        }

        $values = $this->initialValues;
        while (Number::of(count($values))->compare($n) < 0) {
            $next = ($this->rule)(
                $values[count($values) - 1],
                $values[count($values) - 2]
            );
            $values[] = $next;
        }

        return $values[count($values) - 1];
    }
}
