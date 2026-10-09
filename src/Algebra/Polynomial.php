<?php

declare(strict_types=1);

namespace Gauss\Algebra;

use Gauss\Number\Number;
use DivisionByZeroError;
use InvalidArgumentException;

/**
 * Immutable polynomial: a0 + a1 x + a2 x^2 + ... + an x^n
 *
 * Coefficients are stored sparsely, keyed by degree.
 * All numeric operations delegate to Number.
 */
final class Polynomial
{
    /** @var array<int, Number> degree => coefficient */
    private readonly array $coefficients;
    private readonly int $degree;

    /** A Number representing 0, used for consistent zero handling. */
    private readonly Number $zero;

    /**
     * @param array<int, Number> $coefficients
     */
    private function __construct(array $coefficients, Number $zero)
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
        $this->degree = $normalized === [] ? 0 : (int) array_key_last($normalized);
    }

    // ------------------------------------------------------------------
    // Factories
    // ------------------------------------------------------------------

    /**
     * @param array<int, Number> $coefficients degree => Number
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

    public static function zero(Number $zero): self
    {
        return new self([], $zero->sub($zero));
    }

    public static function one(Number $reference): self
    {
        $zero = $reference->sub($reference);
        $one  = self::oneOf($reference);

        return new self([0 => $one], $zero);
    }

    public static function constant(Number $value): self
    {
        $zero = $value->sub($value);

        if (self::isZeroValue($value)) {
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
        return $this->degree;
    }

    public function coefficient(int $degree): Number
    {
        if ($degree < 0) {
            throw new InvalidArgumentException(
                'Degree must be >= 0, got ' . $degree
            );
        }

        return $this->coefficients[$degree] ?? $this->zero;
    }

    public function offPrecision(): self
    {
        $coefficients = [];

        foreach ($this->coefficients as $degree => $coefficient) {
            $coefficients[$degree] = $coefficient->offPrecision();
        }

        return new self($coefficients, $this->zero->offPrecision());
    }

    /**
     * @return array<int, Number>
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

    public function leadingCoefficient(): Number
    {
        if ($this->coefficients === []) {
            return $this->zero;
        }

        return $this->coefficients[$this->degree()];
    }

    public function constantTerm(): Number
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

    public function scale(Number $scalar): Polynomial
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

        $quotientCoefficients = [];
        $remainderCoefficients = $this->coefficients;
        $remainderDegree = $this->degree;

        $divisorDegree    = $divisor->degree();
        $divisorLeading   = $divisor->leadingCoefficient();

        while ($remainderCoefficients !== []
            && $remainderDegree >= $divisorDegree
        ) {
            $remainderLeading = $remainderCoefficients[$remainderDegree];
            $termDegree = $remainderDegree - $divisorDegree;
            $termCoefficient = $remainderLeading->div($divisorLeading);

            if (self::isZeroValue($termCoefficient)) {
                throw new \RuntimeException(
                    'Polynomial division cannot progress: leading term division did not produce a valid quotient term.'
                );
            }

            $quotientCoefficients[$termDegree] = isset($quotientCoefficients[$termDegree])
                ? $quotientCoefficients[$termDegree]->add($termCoefficient)
                : $termCoefficient;

            foreach ($divisor->coefficients as $degree => $coefficient) {
                $targetDegree = $termDegree + $degree;
                $current = $remainderCoefficients[$targetDegree] ?? $this->zero;
                $updated = $current->sub($termCoefficient->mul($coefficient));

                if (self::isZeroValue($updated)) {
                    unset($remainderCoefficients[$targetDegree]);
                } else {
                    $remainderCoefficients[$targetDegree] = $updated;
                }
            }

            $nextRemainderDegree = $remainderDegree;

            while (
                $nextRemainderDegree >= 0
                && !isset($remainderCoefficients[$nextRemainderDegree])
            ) {
                $nextRemainderDegree--;
            }

            if ($nextRemainderDegree >= $remainderDegree) {
                throw new \RuntimeException(
                    'Polynomial division cannot progress: the remainder degree did not decrease.'
                );
            }

            $remainderDegree = $nextRemainderDegree;
        }

        return new PolynomialDivision(
            new self($quotientCoefficients, $this->zero),
            new self($remainderCoefficients, $this->zero)
        );
    }

    // ------------------------------------------------------------------
    // Evaluation (Horner's method)
    // ------------------------------------------------------------------

    public function evaluate(Number $x): Number
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

    public function integral(Number $constant): Polynomial
    {
        $result = [];

        foreach ($this->coefficients as $degree => $coefficient) {
            $newDegree = $degree + 1;
            $n         = $this->intValue($newDegree);

            $result[$newDegree] = $coefficient->div($n);
        }

        // + C. The integrated original terms always shift degree by +1,
        // so there is no degree-0 term produced by the polynomial itself.
        $result[0] = $constant;

        return new self($result, $this->zero);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private static function isZeroValue(Number $value): bool
    {
        return $value->compare(0) === 0;
    }

    /**
    * Build a Number equal to integer $n.
     */
    private function intValue(int $n): Number
    {
        return Number::of($n);
    }

    private static function oneOf(Number $reference): Number
    {
        return $reference->one();
    }
}