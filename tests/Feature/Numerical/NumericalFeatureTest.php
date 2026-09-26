<?php

declare(strict_types=1);

namespace Gauss\Tests\Feature\Numerical;

use Gauss\Number\Number;
use Gauss\Numerical\Integration\SimpsonRule;
use Gauss\Numerical\Root\Bisection;
use PHPUnit\Framework\TestCase;

final class NumericalFeatureTest extends TestCase
{
    public function testItIntegratesAndSolvesAFunctionUsingNumberInputs(): void
    {
        $integral = SimpsonRule::integrate(
            static fn (Number $x): Number => $x->pow(2),
            0,
            1,
            20,
        );
        $root = Bisection::solve(
            static fn (Number $x): Number => $x->pow(2)->sub(4),
            0,
            3,
            '0.0000001',
        );

        self::assertLessThanOrEqual(0, $integral->sub(Number::of(1)->div(3))->abs()->compare('0.000000000001'));
        self::assertLessThanOrEqual(0, $root->sub(2)->abs()->compare('0.0000001'));
    }
}