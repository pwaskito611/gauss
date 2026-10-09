<?php

declare(strict_types=1);

namespace Gauss\Linear;

use DivisionByZeroError;
use Gauss\Number\Number;
use InvalidArgumentException;
use LogicException;

/** Immutable rectangular matrix over Number. */
final class Matrix
{
    /** @var list<list<Number>> */
    private readonly array $values;
    private readonly int $rowCount;
    private readonly int $columnCount;
    private readonly Number $zero;

    /** @param list<list<Number>> $values */
    private function __construct(array $values, Number $zero)
    {
        if ($values === [] || $values[0] === []) {
            throw new InvalidArgumentException('Matrix must contain at least one column and row.');
        }

        $columns = count($values[0]);
        foreach ($values as $row) {
            if (count($row) !== $columns) {
                throw new InvalidArgumentException('Matrix rows must have equal length.');
            }
            foreach ($row as $value) {
                if (! $value instanceof Number) {
                    throw new InvalidArgumentException('Matrix values must be Number instances.');
                }
            }
        }

        $this->values = array_map(static fn (array $row): array => array_values($row), $values);
        $this->rowCount = count($values);
        $this->columnCount = $columns;
        $this->zero = $zero;
    }

    /** @param array<array<int|float|string|Number>> $rows */
    public static function of(array $rows): self
    {
        if ($rows === [] || $rows[0] === []) {
            throw new InvalidArgumentException('Matrix must contain at least one column and row.');
        }

        $converted = [];
        foreach ($rows as $row) {
            $converted[] = array_map(
                static fn (int|float|string|Number $value): Number => Number::of($value),
                $row
            );
        }

        $first = $converted[0][0];
        return new self($converted, $first->sub($first));
    }

    public static function zero(int $rows, int $columns): self
    {
        if ($rows < 1 || $columns < 1) {
            throw new InvalidArgumentException('Matrix dimensions must be >= 1.');
        }

        return self::of(array_fill(0, $rows, array_fill(0, $columns, 0)));
    }

    public static function identity(int $size): self
    {
        if ($size < 1) {
            throw new InvalidArgumentException('Matrix size must be >= 1.');
        }

        $rows = [];
        for ($row = 0; $row < $size; $row++) {
            $values = array_fill(0, $size, 0);
            $values[$row] = 1;
            $rows[] = $values;
        }

        return self::of($rows);
    }

    /** @param array<int|float|string|Number> $diagonal */
    public static function diagonal(array $diagonal): self
    {
        if ($diagonal === []) {
            throw new InvalidArgumentException('Diagonal must contain at least one value.');
        }

        $size = count($diagonal);
        $rows = [];
        foreach ($diagonal as $row) {
            $values = array_fill(0, $size, 0);
            $values[count($rows)] = $row;
            $rows[] = $values;
        }

        return self::of($rows);
    }

    public function rows(): int
    {
        return $this->rowCount;
    }

    public function columns(): int
    {
        return $this->columnCount;
    }

    /** @return array{0:int,1:int} */
    public function shape(): array
    {
        return [$this->rowCount, $this->columnCount];
    }

    public function get(int $row, int $column): Number
    {
        $this->assertIndex($row, $column);
        return $this->values[$row][$column];
    }

    public function offPrecision(): self
    {
        $rows = array_map(
            static fn (array $row): array => array_map(
                static fn (Number $value): Number => $value->offPrecision(),
                $row
            ),
            $this->values
        );

        return new self($rows, $this->zero->offPrecision());
    }

    public function row(int $index): Vector
    {
        if ($index < 0 || $index >= $this->rowCount) {
            throw new InvalidArgumentException('Matrix row index is outside the matrix.');
        }

        return Vector::of(...$this->values[$index]);
    }

    public function column(int $index): Vector
    {
        if ($index < 0 || $index >= $this->columnCount) {
            throw new InvalidArgumentException('Matrix column index is outside the matrix.');
        }

        $values = [];
        for ($row = 0; $row < $this->rowCount; $row++) {
            $values[] = $this->values[$row][$index];
        }

        return Vector::of(...$values);
    }

    public function diagonalValues(): Vector
    {
        if (! $this->isSquare()) {
            throw new LogicException('Diagonal requires a square matrix.');
        }

        $values = [];
        for ($index = 0; $index < $this->rowCount; $index++) {
            $values[] = $this->values[$index][$index];
        }

        return Vector::of(...$values);
    }

    public function transpose(): self
    {
        $rows = [];
        for ($column = 0; $column < $this->columnCount; $column++) {
            $row = [];
            for ($index = 0; $index < $this->rowCount; $index++) {
                $row[] = $this->values[$index][$column];
            }
            $rows[] = $row;
        }

        return new self($rows, $this->zero);
    }

    public function add(self $other): self
    {
        $this->assertSameShape($other);
        $rows = [];
        for ($row = 0; $row < $this->rowCount; $row++) {
            $result = [];
            for ($column = 0; $column < $this->columnCount; $column++) {
                $result[] = $this->values[$row][$column]->add($other->values[$row][$column]);
            }
            $rows[] = $result;
        }
        return new self($rows, $this->zero);
    }

    public function sub(self $other): self
    {
        $this->assertSameShape($other);
        $rows = [];
        for ($row = 0; $row < $this->rowCount; $row++) {
            $result = [];
            for ($column = 0; $column < $this->columnCount; $column++) {
                $result[] = $this->values[$row][$column]->sub($other->values[$row][$column]);
            }
            $rows[] = $result;
        }
        return new self($rows, $this->zero);
    }

    public function scale(Number $scalar): self
    {
        $rows = [];
        foreach ($this->values as $row) {
            $rows[] = array_map(static fn (Number $value): Number => $value->mul($scalar), $row);
        }
        return new self($rows, $this->zero);
    }

    public function negate(): self
    {
        return $this->scale(Number::of(-1));
    }

    public function multiply(self $other): self
    {
        if ($this->columnCount !== $other->rowCount) {
            throw new InvalidArgumentException('Matrix dimensions are not compatible for multiplication.');
        }

        $rows = [];
        for ($row = 0; $row < $this->rowCount; $row++) {
            $resultRow = [];
            for ($column = 0; $column < $other->columnCount; $column++) {
                $value = $this->zero;
                for ($index = 0; $index < $this->columnCount; $index++) {
                    $value = $value->add($this->values[$row][$index]->mul($other->values[$index][$column]));
                }
                $resultRow[] = $value;
            }
            $rows[] = $resultRow;
        }

        return new self($rows, $this->zero);
    }

    public function multiplyVector(Vector $vector): Vector
    {
        if ($this->columnCount !== $vector->dimension()) {
            throw new InvalidArgumentException('Matrix columns must match vector dimension.');
        }

        $values = [];
        for ($row = 0; $row < $this->rowCount; $row++) {
            $value = $this->zero;
            for ($column = 0; $column < $this->columnCount; $column++) {
                $value = $value->add($this->values[$row][$column]->mul($vector->get($column)));
            }
            $values[] = $value;
        }

        return Vector::of(...$values);
    }

    public function trace(): Number
    {
        if (! $this->isSquare()) {
            throw new LogicException('Trace requires a square matrix.');
        }

        $result = $this->zero;
        for ($index = 0; $index < $this->rowCount; $index++) {
            $result = $result->add($this->values[$index][$index]);
        }
        return $result;
    }

    public function determinant(): Number
    {
        if (! $this->isSquare()) {
            throw new LogicException('Determinant requires a square matrix.');
        }

        if ($this->rowCount === 1) {
            return $this->values[0][0];
        }

        try {
            return LUDecomposition::of($this)->determinant();
        } catch (DivisionByZeroError $exception) {
            return $this->zero;
        }
    }

    public function minor(int $row, int $column): self
    {
        $this->assertIndex($row, $column);
        if ($this->rowCount === 1) {
            throw new LogicException('A 1x1 matrix has no matrix minor.');
        }

        $rows = [];
        foreach ($this->values as $rowIndex => $values) {
            if ($rowIndex === $row) {
                continue;
            }

            $minorRow = [];
            foreach ($values as $columnIndex => $value) {
                if ($columnIndex !== $column) {
                    $minorRow[] = $value;
                }
            }
            $rows[] = $minorRow;
        }

        return new self($rows, $this->zero);
    }

    public function cofactor(int $row, int $column): Number
    {
        $this->assertIndex($row, $column);
        if ($this->rowCount === 1 && $this->columnCount === 1) {
            return $this->values[0][0]->one();
        }

        $minor = $this->minor($row, $column)->determinant();
        return (($row + $column) % 2 === 0) ? $minor : $this->zero->sub($minor);
    }

    public function adjugate(): self
    {
        if (! $this->isSquare()) {
            throw new LogicException('Adjugate requires a square matrix.');
        }
        if ($this->rowCount === 1) {
            return new self([[$this->values[0][0]->one()]], $this->zero);
        }

        $rows = [];
        for ($row = 0; $row < $this->rowCount; $row++) {
            $values = [];
            for ($column = 0; $column < $this->columnCount; $column++) {
                $values[] = $this->cofactor($column, $row);
            }
            $rows[] = $values;
        }
        return new self($rows, $this->zero);
    }

    public function inverse(): self
    {
        if (! $this->isSquare()) {
            throw new LogicException('Inverse requires a square matrix.');
        }

        try {
            $lu = LUDecomposition::of($this);
        } catch (DivisionByZeroError $exception) {
            throw new DivisionByZeroError('Cannot invert a singular matrix.');
        }

        $inverseRows = array_fill(0, $this->rowCount, array_fill(0, $this->columnCount, $this->zero));
        $basisRows = [];
        $one = $this->values[0][0]->one();
        for ($row = 0; $row < $this->rowCount; $row++) {
            $basisRows[] = array_map(
                fn (int $column): Number => $column === $row ? $one : $this->zero,
                range(0, $this->rowCount - 1)
            );
        }
        $basis = new self($basisRows, $this->zero);

        for ($column = 0; $column < $this->columnCount; $column++) {
            $solution = $lu->solve($basis->column($column));
            foreach ($solution->values() as $row => $value) {
                $inverseRows[$row][$column] = $value;
            }
        }

        return new self($inverseRows, $this->zero);
    }

    public function rank(): int
    {
        return RowReduction::of($this)->rank();
    }

    public function isSquare(): bool { return $this->rowCount === $this->columnCount; }

    public function isDiagonal(): bool
    {
        if (! $this->isSquare()) return false;
        for ($row = 0; $row < $this->rowCount; $row++) {
            for ($column = 0; $column < $this->columnCount; $column++) {
                if ($row !== $column && ! $this->isZeroValue($this->values[$row][$column])) return false;
            }
        }
        return true;
    }

    public function isSymmetric(): bool
    {
        return $this->isSquare() && $this->equals($this->transpose());
    }

    public function isIdentity(): bool
    {
        return $this->isSquare() && $this->equals(self::identity($this->rowCount));
    }

    public function isSingular(): bool
    {
        return $this->isSquare() && $this->isZeroValue($this->determinant());
    }

    public function equals(self $other): bool
    {
        if ($this->shape() !== $other->shape()) return false;
        for ($row = 0; $row < $this->rowCount; $row++) {
            for ($column = 0; $column < $this->columnCount; $column++) {
                if ($this->values[$row][$column]->compare($other->values[$row][$column]) !== 0) return false;
            }
        }
        return true;
    }

    private function assertIndex(int $row, int $column): void
    {
        if ($row < 0 || $row >= $this->rowCount || $column < 0 || $column >= $this->columnCount) {
            throw new InvalidArgumentException('Matrix index is outside the matrix.');
        }
    }

    private function assertSameShape(self $other): void
    {
        if ($this->shape() !== $other->shape()) throw new InvalidArgumentException('Matrix shapes must match.');
    }

    private static function isZeroValue(Number $value): bool
    {
        return $value->compare(0) === 0;
    }
}
