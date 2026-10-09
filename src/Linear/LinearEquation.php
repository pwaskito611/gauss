<?php

declare(strict_types=1);

namespace Gauss\Linear;

use Gauss\Number\Number;
use InvalidArgumentException;

final class LinearEquation
{
    private function __construct(
        private readonly Vector $coefficients,
        private readonly Number $constant,
    ) {
    }

    public static function of(Vector $coefficients, int|float|string|Number $constant): self
    {
        return new self($coefficients, Number::of($constant));
    }

    public function coefficients(): Vector
    {
        return $this->coefficients;
    }

    public function constant(): Number
    {
        return $this->constant;
    }

    public function offPrecision(): self
    {
        return new self($this->coefficients->offPrecision(), $this->constant->offPrecision());
    }

    public function variables(): int
    {
        return $this->coefficients->dimension();
    }

    public function evaluate(Vector $values): Number
    {
        if ($values->dimension() !== $this->variables()) {
            throw new InvalidArgumentException('Variable count must match equation coefficients.');
        }

        return $this->coefficients->dot($values);
    }

    public function normalize(): self
    {
        foreach ($this->coefficients->values() as $coefficient) {
            if (! $this->isZeroValue($coefficient)) {
                $factor = $coefficient->one()->div($coefficient);
                return $this->scale($factor);
            }
        }

        if (! $this->isZeroValue($this->constant)) {
            return $this->scale($this->constant->one()->div($this->constant));
        }

        return $this;
    }

    public function scale(Number $scalar): self
    {
        return new self($this->coefficients->scale($scalar), $this->constant->mul($scalar));
    }

    public function toRow(): Matrix
    {
        $row = $this->coefficients->values();
        $row[] = $this->constant;
        return Matrix::of([$row]);
    }

    private static function isZeroValue(Number $value): bool
    {
        return $value->compare(0) === 0;
    }
}
