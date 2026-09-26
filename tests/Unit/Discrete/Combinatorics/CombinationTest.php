<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Discrete\Combinatorics;

use Gauss\Discrete\Combinatorics\Combination;
use Gauss\Discrete\Combinatorics\Multinomial;
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
        self::assertSame('120', Combination::of(Number::of(10), Number::of(3))->value());
        self::assertSame(
            '4999999999999999999950000000000000000000',
            Combination::of(Number::of('100000000000000000000'), Number::of(2))->value(),
        );
        self::assertSame('30', Multinomial::of(Number::of(5), [Number::of(2), Number::of(1), Number::of(2)])->value());
    }

    public function testRejectsInvalidRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Combination::of(Number::of(3), Number::of(4));
    }
}
