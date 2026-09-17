<?php

declare(strict_types=1);

namespace Gauss\Algebra;

use Gauss\Number\NumericValue;
use DivisionByZeroError;
use InvalidArgumentException;

/**
 * Immutable polynomial: a0 + a1 x + a2 x^2 + ... + an x^n
 *
 * Coefficients are stored sparsely, keyed by degree.
 * All numeric operations delegate to NumericValue.
 */
final class Polynomial
{
    /** @var array<int, NumericValue> degree => coefficient */
    private readonly array $coefficients;

    /** A NumericValue representing 0, used for type-consistent zero. */
    private readonly NumericValue $zero;

    /**
     * @param array<int, NumericValue> $coefficients
     */
    private function __construct(array $coefficients, NumericValue $zero)
    {
        $this->zero = $zero;

        // Normalize: sort, drop zero coefficients, keep sparse.
        ksort($coefficients);

        $normalized = [];

        foreach ($coefficients as $degree => $coefficient) {
            if ($degree < 0) {
                throw new InvalidArgumentException(
                    'Polynomial degree must be >= 0, got ' . $degree
                );
            }

            if (! $this->isZeroValue($coefficient)) {
                $normalized[$degree] = $coefficient;
            }
        }

        $this->coefficients = $normalized;
    }

    // ------------------------------------------------------------------
    // Factories
    // ------------------------------------------------------------------

    /**
     * @param array<int, NumericValue> $coefficients degree => NumericValue
     */
    public static function of(array $coefficients): self
    {
        if ($coefficients === []) {
            throw new InvalidArgumentException(
                'Polynomial::of() requires at least one coefficient.'
            );
        }

        // Derive a zero of the correct numeric type from the first entry.
        $first = reset($coefficients);
        $zero  = $first->sub($first);

        return new self($coefficients, $zero);
    }

    public static function zero(NumericValue $zero): self
    {
        return new self([], $zero->sub($zero));
    }

    public static function one(NumericValue $reference): self
    {
        $zero = $reference->sub($reference);
        $one  = self::oneOf($reference);

        return new self([0 => $one], $zero);
    }

    public static function constant(NumericValue $value): self
    {
        $zero = $value->sub($value);

        if ($zero->value() === $value->value()) {
            // value is zero → represent as zero polynomial
            return new self([], $zero);
        }

        return new self([0 => $value], $zero);
    }

    // ------------------------------------------------------------------
    // Inspection
    // ------------------------------------------------------------------

    public function degree(): int
    {
        if ($this->coefficients === []) {
            return 0; // zero polynomial convention
        }

        return max(array_keys($this->coefficients));
    }

    public function coefficient(int $degree): NumericValue
    {
        if ($degree < 0) {
            throw new InvalidArgumentException(
                'Degree must be >= 0, got ' . $degree
            );
        }

        return $this->coefficients[$degree] ?? $this->zero;
    }

    /**
     * @return array<int, NumericValue>
     */
    public function coefficients(): array
    {
        return $this->coefficients;
    }

    /**
     * @return Monomial[]
     */
    public function terms(): array
    {
        $terms = [];

        foreach ($this->coefficients as $degree => $coefficient) {
            $terms[] = new Monomial($coefficient, $degree);
        }

        return $terms;
    }

    public function leadingCoefficient(): NumericValue
    {
        if ($this->coefficients === []) {
            return $this->zero;
        }

        return $this->coefficients[$this->degree()];
    }

    public function constantTerm(): NumericValue
    {
        return $this->coefficient(0);
    }

    public function isZero(): bool
    {
        return $this->coefficients === [];
    }

    public function isConstant(): bool
    {
        return $this->degree() === 0;
    }

    public function isMonic(): bool
    {
        if ($this->isZero()) {
            return false;
        }

        $leading = $this->leadingCoefficient();
        $one     = $leading->div($leading);

        return $leading->compare($one) === 0;
    }

    // ------------------------------------------------------------------
    // Arithmetic
    // ------------------------------------------------------------------

    public function add(Polynomial $other): Polynomial
    {
        $result = $this->coefficients;

        foreach ($other->coefficients as $degree => $coefficient) {
            $result[$degree] = isset($result[$degree])
                ? $result[$degree]->add($coefficient)
                : $coefficient;
        }

        return new self($result, $this->zero);
    }

    public function sub(Polynomial $other): Polynomial
    {
        return $this->add($other->negate());
    }

    public function mul(Polynomial $other): Polynomial
    {
        $result = [];

        foreach ($this->coefficients as $d1 => $c1) {
            foreach ($other->coefficients as $d2 => $c2) {
                $degree    = $d1 + $d2;
                $product   = $c1->mul($c2);

                $result[$degree] = isset($result[$degree])
                    ? $result[$degree]->add($product)
                    : $product;
            }
        }

        return new self($result, $this->zero);
    }

    public function negate(): Polynomial
    {
        $result = [];

        foreach ($this->coefficients as $degree => $coefficient) {
            // -a = 0 - a
            $result[$degree] = $this->zero->sub($coefficient);
        }

        return new self($result, $this->zero);
    }

    public function scale(NumericValue $scalar): Polynomial
    {
        if ($this->isZero()) {
            return $this;
        }

        $result = [];

        foreach ($this->coefficients as $degree => $coefficient) {
            $result[$degree] = $coefficient->mul($scalar);
        }

        return new self($result, $this->zero);
    }

    // ------------------------------------------------------------------
    // Division
    // ------------------------------------------------------------------

    public function divide(Polynomial $divisor): PolynomialDivision
    {
        if ($divisor->isZero()) {
            throw new DivisionByZeroError(
                'Cannot divide polynomial by zero polynomial.'
            );
        }

        if ($this->isZero()) {
            return new PolynomialDivision(
                self::zero($this->zero),
                self::zero($this->zero)
            );
        }

        $quotient  = self::zero($this->zero);
        $remainder = $this;

        $divisorDegree    = $divisor->degree();
        $divisorLeading   = $divisor->leadingCoefficient();

        while (! $remainder->isZero()
            && $remainder->degree() >= $divisorDegree
        ) {
            $remainderDegree  = $remainder->degree();
            $remainderLeading = $remainder->leadingCoefficient();

            $termDegree = $remainderDegree - $divisorDegree;

            // coefficient = remainderLeading / divisorLeading
            $termCoefficient = $remainderLeading->div($divisorLeading);

            $term = new Polynomial(
                [$termDegree => $termCoefficient],
                $this->zero
            );

            $quotient  = $quotient->add($term);
            $remainder = $remainder->sub($term->mul($divisor));
        }

        return new PolynomialDivision($quotient, $remainder);
    }

    // ------------------------------------------------------------------
    // Evaluation (Horner's method)
    // ------------------------------------------------------------------

    public function evaluate(NumericValue $x): NumericValue
    {
        if ($this->isZero()) {
            return $this->zero;
        }

        $degree  = $this->degree();
        $result  = $this->coefficient($degree); // leading

        for ($i = $degree - 1; $i >= 0; $i--) {
            $result = $result->mul($x)->add($this->coefficient($i));
        }

        return $result;
    }

    // ------------------------------------------------------------------
    // Calculus
    // ------------------------------------------------------------------

    public function derivative(): Polynomial
    {
        if ($this->isConstant()) {
            return self::zero($this->zero);
        }

        $result = [];

        foreach ($this->coefficients as $degree => $coefficient) {
            if ($degree === 0) {
                continue;
            }

            $n = $this->intValue($degree);

            $result[$degree - 1] = $coefficient->mul($n);
        }

        return new self($result, $this->zero);
    }

    public function integral(NumericValue $constant): Polynomial
    {
        $result = [];

        foreach ($this->coefficients as $degree => $coefficient) {
            $newDegree = $degree + 1;
            $n         = $this->intValue($newDegree);

            $result[$newDegree] = $coefficient->div($n);
        }

        // + C
        $result[0] = isset($result[0])
            ? $result[0]->add($constant)
            : $constant;

        return new self($result, $this->zero);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function isZeroValue(NumericValue $value): bool
    {
        $zero = $value->sub($value);

        return $value->value() === $zero->value();
    }

    /**
     * Build a NumericValue equal to integer $n, type-consistent with
     * this polynomial's zero (i.e. with existing coefficients).
     */
    private function intValue(int $n): NumericValue
    {
        if ($n === 0) {
            return $this->zero;
        }

        // Derive 1 from $this->zero:  1 = 0 / 0? No — 0/0 undefined.
        // Use any non-zero coefficient if present.
        $one = null;

        foreach ($this->coefficients as $coefficient) {
            if (! $this->isZeroValue($coefficient)) {
                $one = $coefficient->div($coefficient);
                break;
            }
        }

        if ($one === null) {
            // Polynomial is zero → no meaningful multiplier needed.
            return $this->zero;
        }

        $value = $one;

        for ($i = 1; $i < $n; $i++) {
            $value = $value->add($one);
        }

        return $value;
    }

    private static function oneOf(NumericValue $reference): NumericValue
    {
        return $reference->one();
    }
}