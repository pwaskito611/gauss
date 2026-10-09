<?php

declare(strict_types=1);

namespace Gauss\Linear;

use Gauss\Linear\Solution\InfiniteSolutions;
use Gauss\Linear\Solution\LinearSystemSolution;
use Gauss\Linear\Solution\NoSolution;
use Gauss\Linear\Solution\UniqueSolution;
use Gauss\Number\Number;
use InvalidArgumentException;

final class LinearSystem
{
    private readonly RowReduction $reduction;

    private function __construct(
        private readonly Matrix $matrix,
        private readonly Vector $rhs,
    ) {
        if ($matrix->rows() !== $rhs->dimension()) {
            throw new InvalidArgumentException('Matrix row count must match right-hand-side dimension.');
        }

        $rows = [];
        for ($row = 0; $row < $matrix->rows(); $row++) {
            $values = [];
            for ($column = 0; $column < $matrix->columns(); $column++) {
                $values[] = $matrix->get($row, $column);
            }
            $values[] = $rhs->get($row);
            $rows[] = $values;
        }

        $this->reduction = RowReduction::of(Matrix::of($rows));
    }

    public static function of(Matrix $matrix, Vector $rhs): self
    {
        return new self($matrix, $rhs);
    }

    public function matrix(): Matrix
    {
        return $this->matrix;
    }

    public function rhs(): Vector
    {
        return $this->rhs;
    }

    public function offPrecision(): self
    {
        return new self($this->matrix->offPrecision(), $this->rhs->offPrecision());
    }

    public function variables(): int
    {
        return $this->matrix->columns();
    }

    public function solve(): LinearSystemSolution
    {
        $reduced = $this->reduction->reducedEchelonForm();
        $pivotColumns = [];
        foreach ($this->reduction->pivotColumns() as $column) {
            if ($column >= $this->variables()) {
                break;
            }
            $pivotColumns[] = $column;
        }

        for ($row = 0; $row < $reduced->rows(); $row++) {
            $allZero = true;
            for ($column = 0; $column < $this->variables(); $column++) {
                if (! $this->isZero($reduced->get($row, $column))) {
                    $allZero = false;
                    break;
                }
            }
            if ($allZero && ! $this->isZero($reduced->get($row, $this->variables()))) {
                return new NoSolution();
            }
        }

        $freeColumns = array_values(array_diff(range(0, $this->variables() - 1), $pivotColumns));
        if ($freeColumns !== []) {
            return new InfiniteSolutions($reduced, $freeColumns);
        }

        $values = array_fill(0, $this->variables(), null);
        foreach ($pivotColumns as $row => $column) {
            $values[$column] = $reduced->get($row, $this->variables());
        }

        return new UniqueSolution(Vector::of(...$values));
    }

    public function solution(): LinearSystemSolution
    {
        return $this->solve();
    }

    public function hasSolution(): bool
    {
        return $this->solve()->hasSolution();
    }

    public function isConsistent(): bool
    {
        return $this->hasSolution();
    }

    private static function isZero(Number $value): bool
    {
        return $value->compare(0) === 0;
    }
}
