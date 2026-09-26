<?php

declare(strict_types=1);

namespace Gauss\Tests\Feature\Algebra;

use Gauss\Algebra\Polynomial;
use Gauss\Number\Number;
use PHPUnit\Framework\TestCase;

final class PolynomialFeatureTest extends TestCase
{
    public function testItEvaluatesAndDifferentiatesAPolynomialThroughPublicApi(): void
    {
        $polynomial = Polynomial::of([
            0 => Number::of(2),
            1 => Number::of(-3),
            2 => Number::of(1),
        ]);

        $value = $polynomial->evaluate(Number::of(4));
        $derivativeValue = $polynomial->derivative()->evaluate(Number::of(4));

        self::assertInstanceOf(Number::class, $value);
        self::assertSame(0, $value->compare(6));
        self::assertSame(0, $derivativeValue->compare(5));
    }
}