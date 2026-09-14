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

    public function add(NumericValue $other): NumericValue
    {
        if ($other instanceof Complex) {
            throw new LogicException(
                'Irrational and Complex arithmetic is not supported.'
            );
        }

        return new self(
            "({$this->expression}) + ({$other->value()})"
        );
    }

    public function sub(NumericValue $other): NumericValue
    {
        if ($other instanceof Complex) {
            throw new LogicException(
                'Irrational and Complex arithmetic is not supported.'
            );
        }

        return new self(
            "({$this->expression}) - ({$other->value()})"
        );
    }

    public function mul(NumericValue $other): NumericValue
    {
        if ($other instanceof Complex) {
            throw new LogicException(
                'Irrational and Complex arithmetic is not supported.'
            );
        }

        return new self(
            "({$this->expression}) * ({$other->value()})"
        );
    }

    public function div(NumericValue $other): NumericValue
    {
        if ($other instanceof Complex) {
            throw new LogicException(
                'Irrational and Complex arithmetic is not supported.'
            );
        }

        if ($other->value() === '0') {
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
}