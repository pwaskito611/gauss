<?php

declare(strict_types=1);

namespace Gauss\Algebra;

/**
 * Immutable result of polynomial division:
 *   P(x) = Q(x) * D(x) + R(x)
 */
final class PolynomialDivision
{
    private readonly Polynomial $quotient;
    private readonly Polynomial $remainder;

    public function __construct(Polynomial $quotient, Polynomial $remainder)
    {
        $this->quotient  = $quotient;
        $this->remainder = $remainder;
    }

    public function quotient(): Polynomial
    {
        return $this->quotient;
    }

    public function remainder(): Polynomial
    {
        return $this->remainder;
    }
}