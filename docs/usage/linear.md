# Linear Algebra

## Overview

Gauss's `Gauss\Linear` module provides vectors, matrices, linear systems,
decompositions, linear maps, and vector spaces. Values are represented with
`Gauss\Number\Number`; linear algebra operations delegate numerical arithmetic
to `Number`.

In default BCMath mode, addition, subtraction, and multiplication use
`Number`'s exact decimal arithmetic on stored values. Operations that divide
or take square roots, including determinant, inverse, norm, normalization, and
QR solving, follow `Number`'s rounded precision behavior. Linear algebra
results should therefore not be described as universally exact. Float mode
uses native PHP floats for the managed calculations.

The types are immutable: operations produce results without changing their
input objects.

`Vector` and `Matrix` can be copied into float mode with `offPrecision()`.
Their numeric operations then propagate that mode through Gauss linear
algebra. BCMath remains the default. See
[Precision modes](precision.md) for examples and numeric trade-offs.

## Vector

### Construction

`Vector::of()` accepts one or more integer, float, string, or `Number` values;
each value is converted through `Number::of()`. A vector cannot have dimension
zero.

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Linear\Vector;

$v = Vector::of(1, 2, 3);
$zero = Vector::zero(3);
$e1 = Vector::basis(3, 0);
```

`Vector::zero($dimension)` creates a zero vector. `Vector::basis($dimension,
$index)` creates the standard basis vector with `1` at the zero-based index
and `0` elsewhere. Dimensions below 1 and basis indices outside
`0..$dimension - 1` throw `InvalidArgumentException`.

Calling `Vector::of()` without values throws `InvalidArgumentException`.

### Arithmetic

`add()` and `sub()` combine vectors component-wise. Their dimensions must
match. `scale(Number $scalar)` multiplies every component by the scalar;
`negate()` multiplies by `-1`.

```php
$v = Vector::of(1, 2, 3);
$w = Vector::of(3, 2, 1);

$sum = $v->add($w);                    // (4, 4, 4)
$difference = $v->sub($w);             // (-2, 0, 2)
$scaled = $v->scale(\Gauss\Number\Number::of(2)); // (2, 4, 6)
$negative = $v->negate();              // (-1, -2, -3)
```

`add()`, `sub()`, and `dot()` throw `InvalidArgumentException` when vector
dimensions differ.

### Geometry

- `dot($other)` returns the scalar dot product as a `Number`.
- `cross($other)` returns the three-dimensional cross product. Both vectors
  must be 3D; otherwise `InvalidArgumentException` is thrown.
- `normSquared()` returns the dot product of the vector with itself.
- `norm()` returns the square root of `normSquared()`, using
  `Number::sqrt()`'s rounded precision. If the squared norm is negative,
  `LogicException` is thrown.
- `distance($other)` returns the norm of the difference between two
  same-dimension vectors.
- `normalize()` returns the vector scaled to unit norm. Normalizing the zero
  vector throws `LogicException`.

```php
$a = Vector::of(1, 0, 0);
$b = Vector::of(0, 1, 0);

$a->dot($b)->value();       // "0"
$a->cross($b)->values();    // (0, 0, 1), as Number values
$a->norm()->value();        // "1"
$a->normalize()->values();  // (1, 0, 0), as Number values
```

### Predicates and relations

- `isZero()` is true when every component compares equal to zero.
- `isOrthogonalTo($other)` is true when the dot product compares equal to
  zero. The dimensions must match.
- `isParallelTo($other)` checks whether vectors are parallel. A zero vector
  is considered parallel to every vector, including another zero vector.
  Dimensions must match.

For 3D vectors, parallelism is determined by whether the cross product is
zero. For other dimensions, the implementation compares component ratios.
That check can involve rounded `Number::div()` results.

### Inspection and mapping

- `dimension()` returns the number of components.
- `get($index)` returns one component as a `Number`; an invalid index throws
  `InvalidArgumentException`.
- `values()` returns the ordered list of components as `Number` objects.
- `map($transform)` applies a callback to each `Number` and converts each
  callback result through `Number::of()`.

```php
$v = Vector::of(1, 2, 3);
$doubled = $v->map(
    static fn (\Gauss\Number\Number $value): \Gauss\Number\Number => $value->mul(2)
);
```

## Matrix

### Construction

`Matrix::of()` accepts a non-empty array of non-empty rows. Entries may be
integers, floats, strings, or `Number` objects and are converted using
`Number::of()`. Every row must have the same number of columns.

```php
use Gauss\Linear\Matrix;

$a = Matrix::of([
    [1, 2],
    [3, 4],
]);

$zero = Matrix::zero(2, 3);
$identity = Matrix::identity(2);
$diagonal = Matrix::diagonal([2, 4, 6]);
```

Zero rows, zero columns, ragged rows, and empty diagonal input are invalid and
throw `InvalidArgumentException`. `zero()` requires both dimensions to be at
least 1; `identity()` requires size at least 1.

### Arithmetic and multiplication

`add()` and `sub()` operate component-wise and require identical shapes.
`scale(Number $scalar)` scales every entry; `negate()` multiplies by `-1`.
`transpose()` swaps rows and columns.

`multiply()` is matrix multiplication: the left matrix's column count must
equal the right matrix's row count. `multiplyVector()` multiplies by a
column-vector and requires its dimension to equal the matrix's column count.
Shape mismatches throw `InvalidArgumentException`.

```php
$a = Matrix::of([
    [1, 2],
    [3, 4],
]);
$b = Matrix::of([
    [2, 0],
    [1, 2],
]);

$product = $a->multiply($b);                 // [[4, 4], [10, 8]]
$image = $a->multiplyVector(Vector::of(5, 6)); // (17, 39)
$sum = $a->add($b);                          // [[3, 2], [4, 6]]
```

### Determinant and inverse

`determinant()` requires a square matrix and throws `LogicException` otherwise.
A 1x1 determinant is the sole element. Larger determinants are computed via
LU decomposition. A singular square matrix has determinant zero; it does not
throw merely because it is singular.

`inverse()` also requires a square matrix. A singular matrix throws
`DivisionByZeroError`; a non-square matrix throws `LogicException`.

```php
$a = Matrix::of([
    [2, 1],
    [1, 3],
]);

$determinant = $a->determinant(); // 5
$inverse = $a->inverse();        // [[3/5, -1/5], [-1/5, 2/5]]
```

Inverse entries involve division and therefore follow `Number`'s rounded
precision model.

### Structural operations

- `minor($row, $column)` removes the selected row and column. Indices must be
  valid. The current implementation throws `LogicException` for any
  one-row matrix (including 1x1); a minor that would have zero columns is not
  a valid `Matrix`.
- `cofactor($row, $column)` returns the signed determinant of the corresponding
  minor. For a 1x1 matrix, the cofactor is `1`.
- `adjugate()` returns the transpose of the cofactor matrix. For a 1x1 matrix,
  it returns `[[1]]`. It requires a square matrix and throws `LogicException`
  otherwise.

```php
$a = Matrix::of([
    [1, 2],
    [3, 4],
]);

$minor = $a->minor(0, 0);       // [[4]]
$cofactor = $a->cofactor(0, 0); // 4
$adjugate = $a->adjugate();     // [[4, -2], [-3, 1]]
```

### Inspection and properties

- `shape()` returns `[rows, columns]`.
- `rows()` and `columns()` return the corresponding dimensions.
- `get($row, $column)` returns an entry as a `Number`.
- `row($index)` and `column($index)` return the selected row or column as a
  `Vector`.
- `diagonalValues()` returns the main diagonal as a `Vector`; it requires a
  square matrix and otherwise throws `LogicException`.
- `trace()` returns the sum of the main diagonal as a `Number`; it also
  requires a square matrix and otherwise throws `LogicException`.
- `rank()` returns the matrix rank, calculated through row reduction.
- `isSquare()` tests whether row and column counts match.
- `isSymmetric()` is true only for a square matrix equal to its transpose.
- `isIdentity()` is true only for a square identity matrix.
- `isDiagonal()` is true only for a square matrix with zero off-diagonal
  entries.
- `isSingular()` is true for a square matrix whose determinant is zero; it is
  false for non-square matrices.
- `equals($other)` compares shape and corresponding numeric values.

Invalid matrix or row/column indices throw `InvalidArgumentException`.

## Linear equations and systems

### LinearEquation

`LinearEquation::of(Vector $coefficients, $constant)` models the equation
`coefficients · variables = constant`. The constant accepts an integer, float,
string, or `Number`.

```php
use Gauss\Linear\LinearEquation;

$equation = LinearEquation::of(Vector::of(2, 3), 5);
// Represents 2x + 3y = 5

$equation->coefficients(); // Vector(2, 3)
$equation->constant();     // Number(5)
$equation->variables();    // 2
$row = $equation->toRow(); // [[2, 3, 5]]
```

- `evaluate($values)` returns the left-hand side dot product, not the
  difference from the equation's constant. The values vector must have the
  same dimension as the coefficients or `InvalidArgumentException` is thrown.
- `normalize()` scales the equation so its first non-zero coefficient is `1`;
  if all coefficients are zero but the constant is non-zero, it scales the
  constant to `1`. The all-zero equation is returned unchanged. Scaling
  involves `Number::div()` and may be rounded.
- `scale(Number $scalar)` multiplies both coefficients and constant.
- `toRow()` returns a 1-row matrix of coefficients followed by the constant.

### LinearSystem

`LinearSystem::of($matrix, $rhs)` represents `matrix * variables = rhs`.
The matrix row count must equal the right-hand-side vector dimension.

```php
use Gauss\Linear\LinearSystem;

$system = LinearSystem::of(
    Matrix::of([[2, 1], [1, 3]]),
    Vector::of(5, 7)
);

$solution = $system->solve();
```

`solve()` returns a `LinearSystemSolution` instance:

- `UniqueSolution` when exactly one solution exists;
- `InfiniteSolutions` when the system is consistent and has free variables;
- `NoSolution` when the system is inconsistent.

The aliases `solution()` and `solve()` return the same kind of result.
`hasSolution()` reports whether the result is not `NoSolution`;
`isConsistent()` is an alias for that check. `variables()` returns the number
of matrix columns. `matrix()` and `rhs()` return the original coefficient
matrix and right-hand-side vector. A row count / RHS dimension mismatch throws
`InvalidArgumentException`.

The result type depends on consistency and the number of free variables:

```php
use Gauss\Linear\Solution\InfiniteSolutions;
use Gauss\Linear\Solution\NoSolution;
use Gauss\Linear\Solution\UniqueSolution;

$unique = LinearSystem::of(
    Matrix::of([[1, 0], [0, 1]]),
    Vector::of(2, 3)
)->solve();
if ($unique instanceof UniqueSolution) {
    $uniqueVector = $unique->vector(); // (2, 3)
}

$infinite = LinearSystem::of(
    Matrix::of([[1, 1]]),
    Vector::of(2)
)->solve();
if ($infinite instanceof InfiniteSolutions) {
    $freeColumns = $infinite->freeColumns(); // [1]
}

$none = LinearSystem::of(
    Matrix::of([[1], [1]]),
    Vector::of(2, 3)
)->solve();
if ($none instanceof NoSolution) {
    $consistent = $none->hasSolution(); // false
}
```

## Solution types

`Gauss\Linear\Solution\LinearSystemSolution` is the result interface. Its
`hasSolution()` method is true for unique and infinite solutions and false for
`NoSolution`.

### UniqueSolution

`UniqueSolution` contains a single solution vector, returned by `vector()`:

```php
use Gauss\Linear\Solution\UniqueSolution;

if ($solution instanceof UniqueSolution) {
    $vector = $solution->vector();
}
```

### InfiniteSolutions

`InfiniteSolutions` exposes the reduced augmented matrix and the zero-based
indices of free variable columns:

```php
use Gauss\Linear\Solution\InfiniteSolutions;

if ($solution instanceof InfiniteSolutions) {
    $freeColumns = $solution->freeColumns();
    $reduced = $solution->reducedMatrix();
}
```

The reduced matrix includes the augmented right-hand-side column.

### NoSolution

`NoSolution` represents an inconsistent system. It has no additional
accessors; use `hasSolution()` to identify it.

## Matrix decompositions

### LUDecomposition

`LUDecomposition::of($matrix)` factors a square matrix with row pivoting.
The permutation, lower, and upper matrices satisfy `P * A = L * U`. This is a
reusable factorization for solving multiple right-hand sides or obtaining the
determinant, rather than only a one-off operation.

```php
use Gauss\Linear\LUDecomposition;

$lu = LUDecomposition::of($a);
$lower = $lu->L();
$upper = $lu->U();
$permutation = $lu->P();
$x = $lu->solve(Vector::of(5, 7));
$determinant = $lu->determinant();
```

`of()` requires a square matrix (`InvalidArgumentException`) and throws
`DivisionByZeroError` if the matrix is singular. `solve()` requires an RHS
vector with dimension equal to the matrix size, or it throws
`InvalidArgumentException`. Factorization and solving use `Number` division,
so their results follow its rounded precision behavior.

### QRDecomposition

`QRDecomposition::of($matrix)` creates a QR factorization using orthogonalized
columns. `Q()` returns the matrix with orthonormal columns and `R()` the
resulting upper-triangular factor.

```php
use Gauss\Linear\QRDecomposition;

$a = Matrix::of([
    [1, 0],
    [0, 1],
    [1, 1],
]);
$qr = QRDecomposition::of($a);
$q = $qr->Q();
$r = $qr->R();
$leastSquares = $qr->solve(Vector::of(1, 2, 2));
```

The input must have at least as many rows as columns and linearly independent
columns; otherwise `InvalidArgumentException` is thrown. `solve()` requires
an RHS whose dimension equals the row count of `Q`. For square input it solves
the square system; for a tall matrix it returns the least-squares solution.
Computing column norms and solving triangular systems can involve `sqrt()` and
`div()`, so results follow `Number`'s rounded precision.

## Eigen analysis

`Eigen` currently supports diagonal square matrices only; a non-diagonal or
non-square input throws `LogicException`. The eigenvalues are the diagonal
entries, paired with the standard basis eigenvectors.

```php
use Gauss\Linear\Eigen;

$eigen = Eigen::of(Matrix::diagonal([2, 3]));

$values = $eigen->values();            // alias: eigenvalues()
$vectors = $eigen->vectors();          // alias: eigenvectors()
$polynomial = $eigen->characteristicPolynomial();
```

For the diagonal matrix with eigenvalues `2` and `3`, the characteristic
polynomial returned by `characteristicPolynomial()` is
`(x - 2)(x - 3) = x^2 - 5x + 6`. The polynomial is represented by
`Gauss\Algebra\Polynomial`, whose coefficient array is keyed by degree.

## Linear maps

`LinearMap::fromMatrix($matrix)` treats the matrix as a linear map from a
domain with dimension equal to its column count to a codomain with dimension
equal to its row count.

```php
use Gauss\Linear\LinearMap;

$map = LinearMap::fromMatrix(Matrix::of([
    [1, 0, 1],
    [0, 1, 1],
]));

$imageOfVector = $map->apply(Vector::of(2, 3, 4));
$kernel = $map->kernel();
$image = $map->image();
```

- `apply($vector)` multiplies by the matrix; input dimension must match the
  domain dimension.
- `kernel()` returns a `VectorSpace` of vectors mapped to zero.
- `image()` returns a `VectorSpace` spanned by the pivot columns.
- `rank()` returns the matrix rank; `nullity()` returns domain dimension
  minus rank.
- `domainDimension()` and `codomainDimension()` return the respective
  dimensions; `matrix()` returns the defining matrix.
- `isInjective()`, `isSurjective()`, and `isBijective()` test injectivity,
  surjectivity, or both, respectively.

Conceptually, `kernel()` and `image()` each return a `VectorSpace`.

## Vector spaces

`VectorSpace::of(...$vectors)` constructs a space from linearly independent
vectors of the same ambient dimension. An empty vector list, mismatched
dimensions, or linearly dependent vectors throws `InvalidArgumentException`.
Use `VectorSpace::zero($ambientDimension)` for the zero subspace, whose basis
is empty.

```php
use Gauss\Linear\VectorSpace;

$space = VectorSpace::of(
    Vector::of(1, 0, 0),
    Vector::of(0, 1, 0)
);

$basis = $space->basis();
$dimension = $space->dimension(); // 2
$contains = $space->contains(Vector::of(2, 3, 0)); // true
$isBasis = $space->isBasis(); // false in ambient dimension 3
$rank = $space->rank();       // 2
$zeroSpace = VectorSpace::zero(3);
```

`contains()` checks ambient dimension and whether adding the tested vector
increases the rank of the basis-column matrix. A dimension mismatch throws
`InvalidArgumentException`. Other methods are `span()` (returns this space),
`isIndependent()` (true for spaces created by this class), `isBasis()` (true
when the basis size equals ambient dimension), and `rank()` (the space
dimension).

## Row reduction helper

`RowReduction` is a helper abstraction used internally by `Matrix::rank()`,
`LinearSystem`, `LinearMap`, and indirectly by `VectorSpace::contains()`. It
is not intended as the primary public interface for linear algebra workflows.

```php
use Gauss\Linear\RowReduction;

$reduction = RowReduction::of(Matrix::of([
    [1, 2],
    [2, 4],
]));

$echelon = $reduction->echelonForm();
$rref = $reduction->reducedEchelonForm();
$rank = $reduction->rank();
$pivots = $reduction->pivotColumns();
$free = $reduction->freeColumns();
$operations = $reduction->operations();
```

`operations()` returns strings describing the row operations used to produce
the reduced form. Echelon and reduced echelon forms use row swaps, scaling,
and row additions as needed.

## Precision model

The following describes the default BCMath mode. Linear types store entries as
`Number` and delegate calculations to that type. Calling `offPrecision()` on a
vector, matrix, or supported linear object selects float arithmetic for
subsequent operations. In default mode, `Number::add()`, `sub()`, and `mul()` are exact decimal operations on
the stored values. `div()` uses rounded decimal division with a
magnitude-aware working scale; `sqrt()` uses half-up rounding to 50 decimal
places. Consequently:

- vector and matrix addition, subtraction, scaling, and products use exact
  decimal operations on their inputs;
- vector norm and normalization involve square root and/or division;
- determinant, inverse, LU/QR solve, row reduction, equation normalization,
  and non-3D parallel checks may involve division;
- QR decomposition also uses vector norms.

These latter results follow `Number`'s rounded precision model. Supplying
decimal strings instead of floats avoids importing binary floating-point
error at construction.

## Edge cases and exceptions

| API | Exception | Condition |
| --- | --- | --- |
| `Vector::of()` | `InvalidArgumentException` | No values supplied |
| `Vector::zero()` / `Vector::basis()` | `InvalidArgumentException` | Dimension below 1 |
| `Vector::basis()` | `InvalidArgumentException` | Index outside vector dimension |
| `Vector::get()` | `InvalidArgumentException` | Index outside vector |
| Vector `add()`, `sub()`, `dot()`, `distance()`, relations | `InvalidArgumentException` | Dimensions do not match |
| `Vector::cross()` | `InvalidArgumentException` | Either vector is not 3D |
| `Vector::normalize()` | `LogicException` | Vector is zero |
| `Vector::norm()` | `LogicException` | Squared norm compares below zero |
| Matrix construction / factories | `InvalidArgumentException` | Empty dimensions, ragged rows, or empty diagonal |
| `Matrix::zero()` / `identity()` | `InvalidArgumentException` | Dimension or size below 1 |
| Matrix arithmetic / multiplication | `InvalidArgumentException` | Incompatible shapes or dimensions |
| Matrix index access | `InvalidArgumentException` | Row, column, or element index out of bounds |
| `Matrix::minor()` | `LogicException` | Matrix has one row (including 1x1) |
| `Matrix::trace()`, `diagonalValues()`, `determinant()`, `inverse()`, `adjugate()` | `LogicException` | Matrix is not square |
| `Matrix::inverse()` / `LUDecomposition::of()` | `DivisionByZeroError` | Matrix is singular |
| `QRDecomposition::of()` | `InvalidArgumentException` | Rows fewer than columns or columns dependent |
| `LinearSystem::of()` | `InvalidArgumentException` | Matrix rows differ from RHS dimension |
| `LinearEquation::evaluate()` | `InvalidArgumentException` | Values dimension differs from coefficient dimension |
| `VectorSpace::of()` | `InvalidArgumentException` | No vectors, dimension mismatch, or dependent vectors |
| `VectorSpace::zero()` | `InvalidArgumentException` | Ambient dimension below 1 |
| `VectorSpace::contains()` | `InvalidArgumentException` | Vector dimension differs from ambient dimension |
| `Eigen::of()` | `LogicException` | Matrix is non-square or non-diagonal |

Singular `Matrix::determinant()` is a special case: it returns zero. Singular
`Matrix::inverse()` and `LUDecomposition::of()` throw `DivisionByZeroError`.

## Related modules

- [number.md](number.md)
- [algebra.md](algebra.md)
- [statistics.md](statistics.md)
- [distribution.md](distribution.md)
- [probability.md](probability.md)
