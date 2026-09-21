<?php

declare(strict_types=1);

namespace Gauss\Linear;

use Gauss\Number\Number;
use InvalidArgumentException;

/** Immutable Gaussian elimination result for a matrix. */
final class RowReduction
{
    private readonly Matrix $matrix;
    /** @var list<string> */
    private readonly array $recordedOperations;

    private function __construct(Matrix $matrix)
    {
        $this->matrix = $matrix;
        $this->recordedOperations = [];
    }

    public static function of(Matrix $matrix): self
    {
        return new self($matrix);
    }

    public function echelonForm(): Matrix
    {
        [$matrix] = $this->reduce(false);
        return $matrix;
    }

    public function reducedEchelonForm(): Matrix
    {
        [$matrix] = $this->reduce(true);
        return $matrix;
    }

    public function rank(): int
    {
        $matrix = $this->echelonForm();
        $rank = 0;
        for ($row = 0; $row < $matrix->rows(); $row++) {
            for ($column = 0; $column < $matrix->columns(); $column++) {
                if (! $this->isZero($matrix->get($row, $column))) {
                    $rank++;
                    break;
                }
            }
        }
        return $rank;
    }

    /** @return list<int> */
    public function pivotColumns(): array
    {
        $matrix = $this->reducedEchelonForm();
        $pivots = [];
        for ($row = 0; $row < $matrix->rows(); $row++) {
            for ($column = 0; $column < $matrix->columns(); $column++) {
                if ($this->isZero($matrix->get($row, $column))) {
                    continue;
                }
                $pivots[] = $column;
                break;
            }
        }
        return $pivots;
    }

    /** @return list<int> */
    public function freeColumns(): array
    {
        $pivotColumns = $this->pivotColumns();
        $free = [];
        for ($column = 0; $column < $this->matrix->columns(); $column++) {
            if (! in_array($column, $pivotColumns, true)) {
                $free[] = $column;
            }
        }
        return $free;
    }

    /** @return list<string> */
    public function operations(): array
    {
        [, $operations] = $this->reduce(true);
        return $operations;
    }

    /** @return array{0:Matrix,1:list<string>} */
    private function reduce(bool $reduced): array
    {
        $rows = [];
        for ($row = 0; $row < $this->matrix->rows(); $row++) {
            $values = [];
            for ($column = 0; $column < $this->matrix->columns(); $column++) {
                $values[] = $this->matrix->get($row, $column);
            }
            $rows[] = $values;
        }

        $operations = [];
        $pivotRow = 0;
        for ($pivotColumn = 0; $pivotColumn < $this->matrix->columns() && $pivotRow < $this->matrix->rows(); $pivotColumn++) {
            $pivot = null;
            for ($row = $pivotRow; $row < $this->matrix->rows(); $row++) {
                if (! $this->isZero($rows[$row][$pivotColumn])) {
                    $pivot = $row;
                    break;
                }
            }
            if ($pivot === null) {
                continue;
            }

            if ($pivot !== $pivotRow) {
                [$rows[$pivotRow], $rows[$pivot]] = [$rows[$pivot], $rows[$pivotRow]];
                $operations[] = "swap {$pivotRow} {$pivot}";
            }

            $pivotValue = $rows[$pivotRow][$pivotColumn];
            if ($reduced || ! $this->isOne($pivotValue)) {
                for ($column = $pivotColumn; $column < $this->matrix->columns(); $column++) {
                    $rows[$pivotRow][$column] = $rows[$pivotRow][$column]->div($pivotValue);
                }
                $operations[] = "scale {$pivotRow}";
            }

            $start = $reduced ? 0 : $pivotRow + 1;
            for ($row = $start; $row < $this->matrix->rows(); $row++) {
                if ($row === $pivotRow || $this->isZero($rows[$row][$pivotColumn])) {
                    continue;
                }
                $factor = $rows[$row][$pivotColumn];
                for ($column = $pivotColumn; $column < $this->matrix->columns(); $column++) {
                    $rows[$row][$column] = $rows[$row][$column]->sub($factor->mul($rows[$pivotRow][$column]));
                }
                $operations[] = "add {$row} {$pivotRow}";
            }

            $pivotRow++;
        }

        return [Matrix::of($rows), $operations];
    }

    private function isZero(Number $value): bool
    {
        return $value->value() === $value->sub($value)->value();
    }

    private function isOne(Number $value): bool
    {
        return $value->value() === $value->one()->value();
    }
}
