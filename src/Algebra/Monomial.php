<?php

declare(strict_types=1);

namespace Gauss\Algebra;

use Gauss\Number\Number;
use InvalidArgumentException;

/**
 * Represents a single polynomial term: a * x^n
 *
 * Immutable. All numeric operations delegate to Number.
 */
final class Monomial
{
    private readonly Number $coefficient;
    private readonly int $degree;

    public function __construct(Number $coefficient, int $degree = 0)
    {
        if ($degree < 0) {
            throw new InvalidArgumentException(
                'Monomial degree must be >= 0, got ' . $degree
            );
        }

        if (self::isZeroNumber($coefficient)) {
            $this->coefficient = $coefficient->sub($coefficient);
            $this->degree      = 0;

            return;
        }

        $this->coefficient = $coefficient;
        $this->degree      = $degree;
    }

    public function coefficient(): Number
    {
        return $this->coefficient;
    }

    public function degree(): int
    {
        return $this->degree;
    }

    public function evaluate(Number $x): Number
    {
        if ($this->isZero() || $this->degree === 0) {
            return $this->coefficient;
        }

        if (self::isZeroNumber($x)) {
            return $this->coefficient->sub($this->coefficient);
        }

        // ax^n = a * (x^n)
        $power = $this->intPower($x, $this->degree);

        return $this->coefficient->mul($power);
    }

    public function add(Monomial $other): Monomial
    {
        if ($this->isZero()) {
            return $other;
        }

        if ($other->isZero()) {
            return $this;
        }

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
        if ($this->isZero() || $other->isZero()) {
            return new self($this->coefficient->sub($this->coefficient), 0);
        }

        return new self(
            $this->coefficient->mul($other->coefficient),
            $this->degree + $other->degree
        );
    }

    public function derivative(): Monomial
    {
        if ($this->isZero() || $this->degree === 0) {
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
        if ($this->isZero()) {
            return new self($this->coefficient->sub($this->coefficient), 0);
        }

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
        return self::isZeroNumber($this->coefficient);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
    * x^n via binary exponentiation, all through Number.
     */
    private function intPower(Number $base, int $exponent): Number
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
    * Build a Number representing integer 1.
     */
    private static function oneOf(Number $reference): Number
    {
        return $reference->one();
    }

    /**
     * Build a Number representing integer $n, consistent with the coefficient.
     */
    private static function fromInt(int $n): Number
    {
        return Number::of($n);
    }

    private static function isZeroNumber(Number $value): bool
    {
        return $value->compare(0) === 0;
    }
}