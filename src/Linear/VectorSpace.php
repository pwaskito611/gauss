<?php

declare(strict_types=1);

namespace Gauss\Linear;

use InvalidArgumentException;

final class VectorSpace
{
    /** @param list<Vector> $basis */
    private function __construct(
        private readonly array $basis,
        private readonly int $ambientDimension,
    ) {
    }

    public static function of(Vector ...$vectors): self
    {
        if ($vectors === []) {
            throw new InvalidArgumentException('VectorSpace requires at least one generator.');
        }

        $dimension = $vectors[0]->dimension();
        foreach ($vectors as $vector) {
            if ($vector->dimension() !== $dimension) {
                throw new InvalidArgumentException('Vector dimensions must match.');
            }
        }

        $matrix = self::columns($vectors);
        if ($matrix->rank() !== count($vectors)) {
            throw new InvalidArgumentException('VectorSpace basis must be linearly independent.');
        }

        return new self(array_values($vectors), $dimension);
    }

    public static function zero(int $ambientDimension): self
    {
        if ($ambientDimension < 1) {
            throw new InvalidArgumentException('Ambient dimension must be >= 1.');
        }

        return new self([], $ambientDimension);
    }

    public function dimension(): int
    {
        return count($this->basis);
    }

    /** @return list<Vector> */
    public function basis(): array
    {
        return $this->basis;
    }

    public function offPrecision(): self
    {
        return new self(
            array_map(static fn (Vector $vector): Vector => $vector->offPrecision(), $this->basis),
            $this->ambientDimension,
        );
    }

    public function contains(Vector $vector): bool
    {
        if ($vector->dimension() !== $this->ambientDimension) {
            throw new InvalidArgumentException('Vector dimension must match the space ambient dimension.');
        }
        if ($this->basis === []) {
            return $vector->isZero();
        }

        $augmented = [];
        for ($row = 0; $row < $this->ambientDimension; $row++) {
            $values = [];
            foreach ($this->basis as $basisVector) {
                $values[] = $basisVector->get($row);
            }
            $values[] = $vector->get($row);
            $augmented[] = $values;
        }

        return Matrix::of($augmented)->rank() === count($this->basis);
    }

    public function span(): self
    {
        return $this;
    }

    public function isIndependent(): bool
    {
        return true;
    }

    public function isBasis(): bool
    {
        return count($this->basis) === $this->ambientDimension;
    }

    public function rank(): int
    {
        return $this->dimension();
    }

    private static function columns(array $vectors): Matrix
    {
        $rows = [];
        $dimension = $vectors[0]->dimension();
        for ($row = 0; $row < $dimension; $row++) {
            $values = [];
            foreach ($vectors as $vector) {
                $values[] = $vector->get($row);
            }
            $rows[] = $values;
        }
        return Matrix::of($rows);
    }
}
