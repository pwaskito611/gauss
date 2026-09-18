<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Discrete\Combinatorics;

use Gauss\Discrete\Combinatorics\Combination;
use Gauss\Number\Number;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CombinationTest extends TestCase
{
    public function testComputesCombinations(): void
    {
        self::assertSame('10', Combination::of(Number::of(5), Number::of(2))->value());
        self::assertSame('1', Combination::of(Number::of(5), Number::of(0))->value());
        self::assertSame('1', Combination::of(Number::of(5), Number::of(5))->value());
    }

    public function testRejectsInvalidRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Combination::of(Number::of(3), Number::of(4));
    }
}
