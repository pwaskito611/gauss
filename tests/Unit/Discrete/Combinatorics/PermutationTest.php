<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Discrete\Combinatorics;

use Gauss\Discrete\Combinatorics\Permutation;
use Gauss\Number\Number;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PermutationTest extends TestCase
{
    public function testComputesPermutations(): void
    {
        self::assertSame('20', Permutation::of(Number::of(5), Number::of(2))->value());
        self::assertSame('120', Permutation::of(Number::of(5), Number::of(5))->value());
    }

    public function testRejectsInvalidRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Permutation::of(Number::of(3), Number::of(4));
    }
}
