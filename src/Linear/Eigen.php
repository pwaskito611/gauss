<?php

declare(strict_types=1);

namespace Gauss\Linear;

use Gauss\Algebra\Polynomial;
use LogicException;

/**
 * Eigen data for diagonal matrices.
 *
 * General eigenvalue iteration is intentionally not claimed by this MVP.
 */
final class Eigen
{
    /** @var list<Vector> */
    private readonly array $eigenvectors;

    private function __construct(private readonly Vector $eigenvalues, array $eigenvectors)
    {
        $this->eigenvectors = $eigenvectors;
    }

    public static function of(Matrix $matrix): self
    {
        if (! $matrix->isSquare() || ! $matrix->isDiagonal()) {
            throw new LogicException('Eigen currently supports diagonal square matrices only.');
        }

        $values = $matrix->diagonalValues();
        $vectors = [];
        for ($index = 0; $index < $matrix->rows(); $index++) {
            $vectors[] = Vector::basis($matrix->rows(), $index);
        }

        return new self($values, $vectors);
    }

    public function values(): Vector { return $this->eigenvalues; }
    public function eigenvalues(): Vector { return $this->eigenvalues; }

    /** @return list<Vector> */
    public function vectors(): array { return $this->eigenvectors; }

    /** @return list<Vector> */
    public function eigenvectors(): array { return $this->eigenvectors; }

    public function characteristicPolynomial(): Polynomial
    {
        $coefficients = [0 => $this->eigenvalues->get(0)->one()];
        foreach ($this->eigenvalues->values() as $eigenvalue) {
            $zero = $eigenvalue->sub($eigenvalue);
            $next = [];
            foreach ($coefficients as $degree => $coefficient) {
                $next[$degree] = ($next[$degree] ?? $zero)->sub($coefficient->mul($eigenvalue));
                $next[$degree + 1] = ($next[$degree + 1] ?? $zero)->add($coefficient);
            }
            $coefficients = $next;
        }

        return Polynomial::of($coefficients);
    }
}
