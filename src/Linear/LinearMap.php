<?php

declare(strict_types=1);

namespace Gauss\Linear;

use InvalidArgumentException;

final class LinearMap
{
    private function __construct(private readonly Matrix $matrix)
    {
    }

    public static function fromMatrix(Matrix $matrix): self
    {
        return new self($matrix);
    }

    public function apply(Vector $vector): Vector
    {
        return $this->matrix->multiplyVector($vector);
    }

    public function matrix(): Matrix
    {
        return $this->matrix;
    }

    public function domainDimension(): int
    {
        return $this->matrix->columns();
    }

    public function codomainDimension(): int
    {
        return $this->matrix->rows();
    }

    public function kernel(): VectorSpace
    {
        $rref = RowReduction::of($this->matrix)->reducedEchelonForm();
        $freeColumns = RowReduction::of($this->matrix)->freeColumns();
        if ($freeColumns === []) {
            return VectorSpace::zero($this->domainDimension());
        }

        $basis = [];
        foreach ($freeColumns as $freeColumn) {
            $zero = $rref->get(0, 0)->sub($rref->get(0, 0));
            $values = array_fill(0, $this->domainDimension(), $zero);
            $values[$freeColumn] = $zero->one();
            $pivotColumns = RowReduction::of($this->matrix)->pivotColumns();
            foreach ($pivotColumns as $row => $pivotColumn) {
                $values[$pivotColumn] = $zero->sub($rref->get($row, $freeColumn));
            }
            $basis[] = Vector::of(...$values);
        }

        return VectorSpace::of(...$basis);
    }

    public function image(): VectorSpace
    {
        $columns = [];
        for ($column = 0; $column < $this->matrix->columns(); $column++) {
            $columns[] = $this->matrix->column($column);
        }
        if ($columns === []) {
            return VectorSpace::zero($this->codomainDimension());
        }

        $pivotColumns = RowReduction::of($this->matrix)->pivotColumns();
        if ($pivotColumns === []) {
            return VectorSpace::zero($this->codomainDimension());
        }
        return VectorSpace::of(...array_map(static fn (int $column): Vector => $columns[$column], $pivotColumns));
    }

    public function rank(): int
    {
        return $this->matrix->rank();
    }

    public function nullity(): int
    {
        return $this->domainDimension() - $this->rank();
    }

    public function isInjective(): bool
    {
        return $this->rank() === $this->domainDimension();
    }

    public function isSurjective(): bool
    {
        return $this->rank() === $this->codomainDimension();
    }

    public function isBijective(): bool
    {
        return $this->isInjective() && $this->isSurjective();
    }
}
