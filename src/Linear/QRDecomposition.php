<?php

declare(strict_types=1);

namespace Gauss\Linear;

use InvalidArgumentException;
use Gauss\Number\Number;

final class QRDecomposition
{
    private function __construct(
        private readonly Matrix $q,
        private readonly Matrix $r,
        private readonly Matrix $source,
    ) {
    }

    public static function of(Matrix $matrix): self
    {
        if ($matrix->rows() < $matrix->columns()) {
            throw new InvalidArgumentException('QR decomposition requires rows >= columns.');
        }

        $columns = [];
        $zero = $matrix->get(0, 0)->sub($matrix->get(0, 0));
        $rValues = [];

        for ($column = 0; $column < $matrix->columns(); $column++) {
            $vector = $matrix->column($column);

            foreach ($columns as $index => $basis) {
                $coefficient = $basis->dot($vector);
                $rValues[$index][$column] = $coefficient;
                $vector = $vector->sub($basis->scale($coefficient));
            }

            $norm = $vector->norm();
            if ($norm->compare(0) === 0) {
                throw new InvalidArgumentException('QR decomposition requires linearly independent columns.');
            }
            $rValues[$column][$column] = $norm;
            $columns[] = $vector->scale($norm->one()->div($norm));
        }

        $qRows = [];
        for ($row = 0; $row < $matrix->rows(); $row++) {
            $values = [];
            foreach ($columns as $column) {
                $values[] = $column->get($row);
            }
            $qRows[] = $values;
        }

        $rRows = [];
        for ($row = 0; $row < $matrix->columns(); $row++) {
            $values = [];
            for ($column = 0; $column < $matrix->columns(); $column++) {
                $values[] = $rValues[$row][$column] ?? $zero;
            }
            $rRows[] = $values;
        }

        return new self(Matrix::of($qRows), Matrix::of($rRows), $matrix);
    }

    public function Q(): Matrix { return $this->q; }
    public function R(): Matrix { return $this->r; }

    public function offPrecision(): self
    {
        return self::of($this->source->offPrecision());
    }

    /**
     * Solves square systems and tall systems in the least-squares sense.
     * Underdetermined matrices are rejected by of().
     */
    public function solve(Vector $rhs): Vector
    {
        if ($rhs->dimension() !== $this->q->rows()) {
            throw new InvalidArgumentException('Right-hand-side dimension must match Q rows.');
        }

        $projected = $this->q->transpose()->multiplyVector($rhs);
        $values = array_fill(0, $this->r->rows(), null);
        for ($row = $this->r->rows() - 1; $row >= 0; $row--) {
            $value = $projected->get($row);
            for ($column = $row + 1; $column < $this->r->columns(); $column++) {
                $value = $value->sub($this->r->get($row, $column)->mul($values[$column]));
            }
            $values[$row] = $value->div($this->r->get($row, $row));
        }

        return Vector::of(...$values);
    }
}
