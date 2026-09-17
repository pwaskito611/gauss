<?php

declare(strict_types=1);

namespace Gauss\Number;

use DivisionByZeroError;
use LogicException;

final class Irrational implements NumericValue
{
    public function __construct(
        private readonly string $expression,
    ) {
    }

    public function one(): NumericValue
    {
        return new self('1');
    }

    public function add(NumericValue $other): NumericValue
    {
        $this->assertSupported($other);

        return new self(
            "({$this->expression}) + ({$other->value()})"
        );
    }

    public function sub(NumericValue $other): NumericValue
    {
        $this->assertSupported($other);

        return new self(
            "({$this->expression}) - ({$other->value()})"
        );
    }

    public function mul(NumericValue $other): NumericValue
    {
        $this->assertSupported($other);

        return new self(
            "({$this->expression}) * ({$other->value()})"
        );
    }

    public function div(NumericValue $other): NumericValue
    {
        $this->assertSupported($other);

        if ($this->isZero($other)) {
            throw new DivisionByZeroError();
        }

        return new self(
            "({$this->expression}) / ({$other->value()})"
        );
    }

    public function value(): string
    {
        return $this->expression;
    }

    private function assertSupported(NumericValue $other): void
    {
        if ($other instanceof Complex) {
            throw new LogicException(
                'Irrational and Complex arithmetic is not supported.'
            );
        }
    }

    private function isZero(NumericValue $other): bool
    {
        /*
         * Rational zero is exact.
         * This avoids relying on its string representation.
         */
        if ($other instanceof Rational) {
            return $other->numerator() === 0;
        }

        /*
         * Real values are represented as decimal strings.
         * BCMath comparison avoids representation-specific checks such as:
         *
         * '0'
         * '0.0'
         * '0.000000'
         */
        if ($other instanceof Real) {
            return bccomp($other->value(), '0', 60) === 0;
        }

        /*
         * Irrational currently represents symbolic expressions.
         * It is not safe to assume that an arbitrary expression such as
         * "sqrt(2) - sqrt(2)" is zero without symbolic evaluation.
         */
        return false;
    }
}