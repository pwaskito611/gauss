<?php

declare(strict_types=1);

namespace Gauss\Linear;

use DivisionByZeroError;
use Gauss\Number\NumericValue;
use InvalidArgumentException;

final class LUDecomposition
{
    private function __construct(
        private readonly Matrix $lower,
        private readonly Matrix $upper,
        private readonly Matrix $permutation,
        private readonly int $swaps,
    ) {
    }

    public static function of(Matrix $matrix): self
    {
        if (! $matrix->isSquare()) {
            throw new InvalidArgumentException('LU decomposition requires a square matrix.');
        }

        $size = $matrix->rows();
        $upper = self::values($matrix);
        $lower = self::identityValues($size);
        $permutation = self::identityValues($size);
        $swaps = 0;
        $zero = $matrix->get(0, 0)->sub($matrix->get(0, 0));

        for ($column = 0; $column < $size; $column++) {
            $pivot = null;
            for ($row = $column; $row < $size; $row++) {
                if (! self::isZero($upper[$row][$column])) {
                    $pivot = $row;
                    break;
                }
            }
            if ($pivot === null) {
                throw new DivisionByZeroError('Cannot decompose a singular matrix.');
            }
            if ($pivot !== $column) {
                [$upper[$pivot], $upper[$column]] = [$upper[$column], $upper[$pivot]];
                [$permutation[$pivot], $permutation[$column]] = [$permutation[$column], $permutation[$pivot]];
                for ($index = 0; $index < $column; $index++) {
                    [$lower[$pivot][$index], $lower[$column][$index]] = [$lower[$column][$index], $lower[$pivot][$index]];
                }
                $swaps++;
            }

            for ($row = $column + 1; $row < $size; $row++) {
                $factor = $upper[$row][$column]->div($upper[$column][$column]);
                $lower[$row][$column] = $factor;
                for ($index = $column; $index < $size; $index++) {
                    $upper[$row][$index] = $upper[$row][$index]->sub($factor->mul($upper[$column][$index]));
                }
            }
        }

        return new self(Matrix::of($lower), Matrix::of($upper), Matrix::of($permutation), $swaps);
    }

    public function L(): Matrix { return $this->lower; }
    public function U(): Matrix { return $this->upper; }
    public function P(): Matrix { return $this->permutation; }

    public function solve(Vector $rhs): Vector
    {
        if ($rhs->dimension() !== $this->lower->rows()) {
            throw new InvalidArgumentException('Right-hand-side dimension must match the matrix.');
        }

        $permuted = $this->permutation->multiplyVector($rhs);
        $forward = [];
        for ($row = 0; $row < $this->lower->rows(); $row++) {
            $value = $permuted->get($row);
            for ($column = 0; $column < $row; $column++) {
                $value = $value->sub($this->lower->get($row, $column)->mul($forward[$column]));
            }
            $forward[$row] = $value;
        }

        $solution = array_fill(0, $this->upper->columns(), null);
        for ($row = $this->upper->rows() - 1; $row >= 0; $row--) {
            $value = $forward[$row];
            for ($column = $row + 1; $column < $this->upper->columns(); $column++) {
                $value = $value->sub($this->upper->get($row, $column)->mul($solution[$column]));
            }
            $solution[$row] = $value->div($this->upper->get($row, $row));
        }

        return Vector::of(...$solution);
    }

    public function determinant(): NumericValue
    {
        $result = $this->upper->get(0, 0)->one();
        for ($index = 0; $index < $this->upper->rows(); $index++) {
            $result = $result->mul($this->upper->get($index, $index));
        }
        if ($this->swaps % 2 === 1) {
            $result = $result->sub($result)->sub($result);
        }
        return $result;
    }

    private static function values(Matrix $matrix): array
    {
        $values = [];
        for ($row = 0; $row < $matrix->rows(); $row++) {
            $values[] = $matrix->row($row)->values();
        }
        return $values;
    }

    private static function identityValues(int $size): array
    {
        $values = [];
        for ($row = 0; $row < $size; $row++) {
            $values[] = array_map(
                static fn (int $column): NumericValue => $column === $row ? \Gauss\Number\Number::of(1) : \Gauss\Number\Number::of(0),
                range(0, $size - 1)
            );
        }
        return $values;
    }

    private static function isZero(NumericValue $value): bool
    {
        return $value->value() === $value->sub($value)->value();
    }
}
