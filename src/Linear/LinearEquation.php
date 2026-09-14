<?php

declare(strict_types=1);

namespace Gauss\Linear;

use Gauss\Number\Number;
use Gauss\Number\NumericValue;
use InvalidArgumentException;

final class LinearEquation
{
    private function __construct(
        private readonly Vector $coefficients,
        private readonly NumericValue $constant,
    ) {
    }

    public static function of(Vector $coefficients, int|float|string|NumericValue $constant): self
    {
        return new self($coefficients, Number::of($constant));
    }

    public function coefficients(): Vector
    {
        return $this->coefficients;
    }

    public function constant(): NumericValue
    {
        return $this->constant;
    }

    public function variables(): int
    {
        return $this->coefficients->dimension();
    }

    public function evaluate(Vector $values): NumericValue
    {
        if ($values->dimension() !== $this->variables()) {
            throw new InvalidArgumentException('Variable count must match equation coefficients.');
        }

        return $this->coefficients->dot($values);
    }

    public function normalize(): self
    {
        foreach ($this->coefficients->values() as $coefficient) {
            if ($coefficient->value() !== $coefficient->sub($coefficient)->value()) {
                $factor = $coefficient->one()->div($coefficient);
                return $this->scale($factor);
            }
        }

        if ($this->constant->value() !== $this->constant->sub($this->constant)->value()) {
            return $this->scale($this->constant->one()->div($this->constant));
        }

        return $this;
    }

    public function scale(NumericValue $scalar): self
    {
        return new self($this->coefficients->scale($scalar), $this->constant->mul($scalar));
    }

    public function toRow(): Matrix
    {
        $row = $this->coefficients->values();
        $row[] = $this->constant;
        return Matrix::of([$row]);
    }
}
