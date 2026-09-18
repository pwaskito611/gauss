<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Discrete\Combinatorics;

use Gauss\Discrete\Combinatorics\Factorial;
use Gauss\Number\Number;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FactorialTest extends TestCase
{
    public function testComputesFactorialsExactly(): void
    {
        self::assertSame('1', Factorial::of(Number::of(0))->value());
        self::assertSame('1', Factorial::of(Number::of(1))->value());
        self::assertSame('120', Factorial::of(Number::of(5))->value());
        self::assertSame('3628800', Factorial::of(Number::of(10))->value());
    }

    public function testRejectsInvalidInput(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Factorial::of(Number::of(-1));
    }

    public function testRejectsNonIntegerValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Factorial::of(Number::of('2.5'));
    }
}
