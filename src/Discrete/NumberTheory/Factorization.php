<?php

declare(strict_types=1);

namespace Gauss\Discrete\NumberTheory;

use Gauss\Number\Number;
use InvalidArgumentException;

final class Factorization
{
    /** @var list<Number> */
    private readonly array $primeFactors;

    /** @var list<Number> */
    private readonly array $exponents;

    /**
     * @param list<Number> $primeFactors
     * @param list<Number> $exponents
     */
    private function __construct(array $primeFactors, array $exponents)
    {
        $this->primeFactors = $primeFactors;
        $this->exponents = $exponents;
    }

    public static function of(Number $n): self
    {
        if (! preg_match('/^-?\d+$/', $n->value())) {
            throw new InvalidArgumentException('Factorization requires an integer value.');
        }

        if ($n->compare(Number::of(0)) < 0) {
            throw new InvalidArgumentException('Factorization is not defined for negative values.');
        }

        if ($n->compare(Number::of(0)) === 0) {
            throw new InvalidArgumentException('Factorization is undefined for zero.');
        }

        if ($n->compare(Number::of(1)) === 0) {
            return new self([], []);
        }

        $remaining = $n;
        $factors = [];
        $exponents = [];
        $divisor = Number::of(2);

        while ($remaining->compare(Number::of(1)) > 0 && $divisor->mul($divisor)->compare($remaining) <= 0) {
            $count = Number::of(0);
            while (Divisibility::isDivisibleBy($remaining, $divisor)) {
                $remaining = $remaining->div($divisor);
                $count = $count->add(1);
            }

            if ($count->compare(Number::of(0)) > 0) {
                $factors[] = $divisor;
                $exponents[] = $count;
            }

            $divisor = $divisor->add(1);
        }

        if ($remaining->compare(Number::of(1)) > 0) {
            $factors[] = $remaining;
            $exponents[] = Number::of(1);
        }

        return new self($factors, $exponents);
    }

    /** @return list<Number> */
    public function primeFactors(): array
    {
        return $this->primeFactors;
    }

    /** @return list<Number> */
    public function exponents(): array
    {
        return $this->exponents;
    }
}
