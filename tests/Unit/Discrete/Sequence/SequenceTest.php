<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Discrete\Sequence;

use Gauss\Discrete\Sequence\ArithmeticSequence;
use Gauss\Discrete\Sequence\GeometricSequence;
use Gauss\Discrete\Sequence\Recurrence;
use Gauss\Number\Number;
use PHPUnit\Framework\TestCase;

final class SequenceTest extends TestCase
{
    public function testArithmeticSequence(): void
    {
        $sequence = ArithmeticSequence::from(Number::of(2), Number::of(3));

        self::assertSame('2', $sequence->first()->value());
        self::assertSame('14', $sequence->at(Number::of(5))->value());
    }

    public function testGeometricSequence(): void
    {
        $sequence = GeometricSequence::from(Number::of(2), Number::of(3));

        self::assertSame('2', $sequence->first()->value());
        self::assertSame('54', $sequence->at(Number::of(4))->value());
    }

    public function testRecurrence(): void
    {
        $sequence = Recurrence::of(
            [Number::of(1), Number::of(1)],
            static fn (Number $previous, Number $before): Number => $previous->add($before)
        );

        self::assertSame('1', $sequence->first()->value());
        self::assertSame('13', $sequence->at(Number::of(7))->value());
    }
}
