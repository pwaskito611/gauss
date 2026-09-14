<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Linear;

use Gauss\Linear\LUDecomposition;
use Gauss\Linear\Matrix;
use Gauss\Linear\Vector;
use PHPUnit\Framework\TestCase;

final class LUDecompositionTest extends TestCase
{
    public function testDecompositionInvariant(): void
    {
        $matrix = Matrix::of([[4, 3], [6, 3]]);
        $lu = LUDecomposition::of($matrix);

        self::assertTrue($lu->P()->multiply($matrix)->equals($lu->L()->multiply($lu->U())));
        self::assertSame('-6', $lu->determinant()->value());
    }

    public function testSolve(): void
    {
        $lu = LUDecomposition::of(Matrix::of([[4, 3], [6, 3]]));
        $solution = $lu->solve(Vector::of(10, 12));

        self::assertSame(['1', '2'], array_map(static fn ($value): string => $value->value(), $solution->values()));
    }
}
