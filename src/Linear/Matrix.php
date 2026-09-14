<?php

declare(strict_types=1);

namespace Gauss\Linear;

use DivisionByZeroError;
use Gauss\Number\Number;
use Gauss\Number\NumericValue;
use InvalidArgumentException;
use LogicException;

/** Immutable rectangular matrix over NumericValue. */
final class Matrix
{
    /** @var list<list<NumericValue>> */
    private readonly array $values;
    private readonly int $rowCount;
    private readonly int $columnCount;
    private readonly NumericValue $zero;

    /** @param list<list<NumericValue>> $values */
    private function __construct(array $values, NumericValue $zero)
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
                if (! $value instanceof NumericValue) {
                    throw new InvalidArgumentException('Matrix values must be NumericValue instances.');
                }
            }
        }

        $this->values = array_map(static fn (array $row): array => array_values($row), $values);
        $this->rowCount = count($values);
        $this->columnCount = $columns;
        $this->zero = $zero;
    }

    /** @param array<array<int|float|string|NumericValue>> $rows */
    public static function of(array $rows): self
    {
        if ($rows === [] || $rows[0] === []) {
            throw new InvalidArgumentException('Matrix must contain at least one column and row.');
        }

        $converted = [];
        foreach ($rows as $row) {
            $converted[] = array_map(
                static fn (int|float|string|NumericValue $value): NumericValue => Number::of($value),
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

    /** @param array<int|float|string|NumericValue> $diagonal */
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

    public function get(int $row, int $column): NumericValue
    {
        $this->assertIndex($row, $column);
        return $this->values[$row][$column];
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

    public function scale(NumericValue $scalar): self
    {
        $rows = [];
        foreach ($this->values as $row) {
            $rows[] = array_map(static fn (NumericValue $value): NumericValue => $value->mul($scalar), $row);
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

    public function trace(): NumericValue
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

    public function determinant(): NumericValue
    {
        if (! $this->isSquare()) {
            throw new LogicException('Determinant requires a square matrix.');
        }

        if ($this->rowCount === 1) {
            return $this->values[0][0];
        }
        if ($this->rowCount === 2) {
            return $this->values[0][0]->mul($this->values[1][1])
                ->sub($this->values[0][1]->mul($this->values[1][0]));
        }

        $result = $this->zero;
        for ($column = 0; $column < $this->columnCount; $column++) {
            $term = $this->values[0][$column]->mul($this->minor(0, $column)->determinant());
            $result = ($column % 2 === 0) ? $result->add($term) : $result->sub($term);
        }
        return $result;
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
            $rows[] = array_values(array_filter(
                $values,
                static fn (NumericValue $value, int $index): bool => $index !== $column,
                ARRAY_FILTER_USE_BOTH
            ));
        }
        return new self($rows, $this->zero);
    }

    public function cofactor(int $row, int $column): NumericValue
    {
        $minor = $this->minor($row, $column)->determinant();
        return (($row + $column) % 2 === 0) ? $minor : $this->zero->sub($minor);
    }

    public function adjugate(): self
    {
        if (! $this->isSquare()) {
            throw new LogicException('Adjugate requires a square matrix.');
        }
        if ($this->rowCount === 1) {
            return self::of([[1]]);
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
        $determinant = $this->determinant();
        if ($this->isZeroValue($determinant)) {
            throw new DivisionByZeroError('Cannot invert a singular matrix.');
        }
        return $this->adjugate()->scale($determinant->one()->div($determinant));
    }

    public function rank(): int
    {
        $rows = $this->values;
        $rank = 0;
        for ($column = 0; $column < $this->columnCount && $rank < $this->rowCount; $column++) {
            $pivot = null;
            for ($row = $rank; $row < $this->rowCount; $row++) {
                if (! $this->isZeroValue($rows[$row][$column])) {
                    $pivot = $row;
                    break;
                }
            }
            if ($pivot === null) {
                continue;
            }
            [$rows[$rank], $rows[$pivot]] = [$rows[$pivot], $rows[$rank]];
            $pivotValue = $rows[$rank][$column];
            for ($row = $rank + 1; $row < $this->rowCount; $row++) {
                if ($this->isZeroValue($rows[$row][$column])) {
                    continue;
                }
                $factor = $rows[$row][$column]->div($pivotValue);
                for ($index = $column; $index < $this->columnCount; $index++) {
                    $rows[$row][$index] = $rows[$row][$index]->sub($factor->mul($rows[$rank][$index]));
                }
            }
            $rank++;
        }
        return $rank;
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
                if ($this->values[$row][$column]->value() !== $other->values[$row][$column]->value()) return false;
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

    private function isZeroValue(NumericValue $value): bool
    {
        return $value->value() === $value->sub($value)->value();
    }
}
