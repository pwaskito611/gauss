<?php

declare(strict_types=1);

namespace Gauss\Algebra;

use Gauss\Number\NumericValue;
use InvalidArgumentException;

/**
 * Represents a single polynomial term: a * x^n
 *
 * Immutable. All numeric operations delegate to NumericValue.
 */
final class Monomial
{
    private readonly NumericValue $coefficient;
    private readonly int $degree;

    public function __construct(NumericValue $coefficient, int $degree = 0)
    {
        if ($degree < 0) {
            throw new InvalidArgumentException(
                'Monomial degree must be >= 0, got ' . $degree
            );
        }

        $this->coefficient = $coefficient;
        $this->degree      = $degree;
    }

    public function coefficient(): NumericValue
    {
        return $this->coefficient;
    }

    public function degree(): int
    {
        return $this->degree;
    }

    public function evaluate(NumericValue $x): NumericValue
    {
        if ($this->degree === 0) {
            return $this->coefficient;
        }

        if ($x->value() === '0') {
            return $this->coefficient->sub($this->coefficient);
        }

        // ax^n = a * (x^n)
        $power = $this->intPower($x, $this->degree);

        return $this->coefficient->mul($power);
    }

    public function add(Monomial $other): Monomial
    {
        if ($this->degree !== $other->degree) {
            throw new InvalidArgumentException(
                'Cannot add monomials of different degrees: '
                . $this->degree . ' vs ' . $other->degree
            );
        }

        return new self(
            $this->coefficient->add($other->coefficient),
            $this->degree
        );
    }

    public function mul(Monomial $other): Monomial
    {
        return new self(
            $this->coefficient->mul($other->coefficient),
            $this->degree + $other->degree
        );
    }

    public function derivative(): Monomial
    {
        if ($this->degree === 0) {
            // d/dx (a) = 0
            return new self(
                $this->coefficient->sub($this->coefficient), // zero of same type
                0
            );
        }

        $newCoefficient = $this->coefficient->mul(
            self::fromInt($this->degree)
        );

        return new self($newCoefficient, $this->degree - 1);
    }

    public function integral(): Monomial
    {
        $newDegree      = $this->degree + 1;
        $newCoefficient = $this->coefficient->div(
            self::fromInt($newDegree)
        );

        return new self($newCoefficient, $newDegree);
    }

    public function isConstant(): bool
    {
        return $this->degree === 0;
    }

    public function isZero(): bool
    {
        $zero = $this->coefficient->sub($this->coefficient);

        return $this->coefficient->value() === $zero->value();
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * x^n via binary exponentiation, all through NumericValue.
     */
    private function intPower(NumericValue $base, int $exponent): NumericValue
    {
        $result = self::oneOf($base);

        if ($exponent === 0) {
            return $result;
        }

        $factor = $base;

        while ($exponent > 0) {
            if (($exponent & 1) === 1) {
                $result = $result->mul($factor);
            }

            $exponent >>= 1;

            if ($exponent > 0) {
                $factor = $factor->mul($factor);
            }
        }

        return $result;
    }

    /**
     * Build a NumericValue representing integer 1, of the same
     * numeric type as $reference, so arithmetic stays type-consistent.
     */
    private static function oneOf(NumericValue $reference): NumericValue
    {
        return $reference->one();
    }

    /**
     * Build a NumericValue representing integer $n, of the same
     * numeric type as the coefficient.
     */
    private function fromInt(int $n): NumericValue
    {
        if ($n === 0) {
            return $this->coefficient->sub($this->coefficient);
        }

        $one   = self::oneOf($this->coefficient);
        $value = $one;

        for ($i = 1; $i < $n; $i++) {
            $value = $value->add($one);
        }

        return $value;
    }
}