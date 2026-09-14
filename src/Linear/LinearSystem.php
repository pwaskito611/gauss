<?php

declare(strict_types=1);

namespace Gauss\Linear;

use Gauss\Linear\Solution\InfiniteSolutions;
use Gauss\Linear\Solution\LinearSystemSolution;
use Gauss\Linear\Solution\NoSolution;
use Gauss\Linear\Solution\UniqueSolution;
use Gauss\Number\NumericValue;
use InvalidArgumentException;

final class LinearSystem
{
    private function __construct(
        private readonly Matrix $matrix,
        private readonly Vector $rhs,
    ) {
        if ($matrix->rows() !== $rhs->dimension()) {
            throw new InvalidArgumentException('Matrix row count must match right-hand-side dimension.');
        }
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

    public function variables(): int
    {
        return $this->matrix->columns();
    }

    public function solve(): LinearSystemSolution
    {
        $rows = [];
        for ($row = 0; $row < $this->matrix->rows(); $row++) {
            $values = [];
            for ($column = 0; $column < $this->matrix->columns(); $column++) {
                $values[] = $this->matrix->get($row, $column);
            }
            $values[] = $this->rhs->get($row);
            $rows[] = $values;
        }

        $reduced = RowReduction::of(Matrix::of($rows))->reducedEchelonForm();
        $pivotColumns = [];
        for ($row = 0; $row < $reduced->rows(); $row++) {
            for ($column = 0; $column < $this->variables(); $column++) {
                if (! $this->isZero($reduced->get($row, $column))) {
                    $pivotColumns[] = $column;
                    break;
                }
            }
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

        return new UniqueSolution(Vector::of(...array_map(
            static fn (NumericValue $value): NumericValue => $value,
            $values
        )));
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

    private function isZero(NumericValue $value): bool
    {
        return $value->value() === $value->sub($value)->value();
    }
}
