<?php

declare(strict_types=1);

namespace Gauss\Linear;

use Gauss\Number\Number;
use InvalidArgumentException;

/** Memoized Gaussian elimination result for a matrix. */
final class RowReduction
{
    private readonly Matrix $matrix;
    private ?Matrix $echelonCache = null;
    private ?Matrix $reducedCache = null;
    /** @var list<int>|null */
    private ?array $pivotCache = null;
    /** @var list<int>|null */
    private ?array $freeCache = null;
    private ?int $rankCache = null;
    /** @var list<string>|null */
    private ?array $reducedOperationsCache = null;

    private function __construct(Matrix $matrix)
    {
        $this->matrix = $matrix;
    }

    public static function of(Matrix $matrix): self
    {
        return new self($matrix);
    }

    public function echelonForm(): Matrix
    {
        return $this->echelonCache ??= $this->reduce(false)[0];
    }

    public function reducedEchelonForm(): Matrix
    {
        return $this->reducedCache ??= $this->reduce(true)[0];
    }

    public function rank(): int
    {
        if ($this->rankCache !== null) {
            return $this->rankCache;
        }

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

        return $this->rankCache = $rank;
    }

    /** @return list<int> */
    public function pivotColumns(): array
    {
        if ($this->pivotCache !== null) {
            return $this->pivotCache;
        }

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

        return $this->pivotCache = $pivots;
    }

    /** @return list<int> */
    public function freeColumns(): array
    {
        if ($this->freeCache !== null) {
            return $this->freeCache;
        }

        $pivotColumns = $this->pivotColumns();
        $free = [];
        for ($column = 0; $column < $this->matrix->columns(); $column++) {
            if (! in_array($column, $pivotColumns, true)) {
                $free[] = $column;
            }
        }

        return $this->freeCache = $free;
    }

    /** @return list<string> */
    public function operations(): array
    {
        if ($this->reducedOperationsCache !== null) {
            return $this->reducedOperationsCache;
        }

        return $this->reducedOperationsCache = $this->reduce(true)[1];
    }

    /** @return array{0:Matrix,1:list<string>} */
    private function reduce(bool $reduced): array
    {
        if ($reduced && $this->reducedCache !== null) {
            return [$this->reducedCache, $this->reducedOperationsCache ?? []];
        }

        if (! $reduced && $this->echelonCache !== null) {
            return [$this->echelonCache, []];
        }

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

        $matrix = Matrix::of($rows);
        if ($reduced) {
            $this->reducedCache = $matrix;
            $this->reducedOperationsCache = $operations;
        } else {
            $this->echelonCache = $matrix;
        }

        return [$matrix, $operations];
    }

    private static function isZero(Number $value): bool
    {
        return $value->compare(0) === 0;
    }

    private static function isOne(Number $value): bool
    {
        return $value->compare(1) === 0;
    }
}
