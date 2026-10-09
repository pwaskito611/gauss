<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Discrete\Sequence;

use Gauss\Discrete\Sequence\ArithmeticSequence;
use Gauss\Discrete\Sequence\GeometricSequence;
use Gauss\Discrete\Sequence\Recurrence;
use Gauss\Number\Number;
use InvalidArgumentException;
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

    public function testNumericSequencesUseFloatArithmeticWhenPrecisionIsDisabled(): void
    {
        $arithmetic = ArithmeticSequence::from(Number::of(2), Number::of(3));
        $floatArithmetic = $arithmetic->offPrecision();
        $geometric = GeometricSequence::from(Number::of(2), Number::of(3));
        $floatGeometric = $geometric->offPrecision();

        self::assertSame('bcmath', $arithmetic->at(Number::of(5))->backend());
        self::assertSame('float', $floatArithmetic->at(Number::of(5))->backend());
        self::assertSame('float', $floatGeometric->at(Number::of(4))->backend());
        self::assertSame('14', $floatArithmetic->at(Number::of(5))->value());
        self::assertSame('54', $floatGeometric->at(Number::of(4))->value());
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

    public function testFloatRecurrencePassesFloatOperandsAndRebindsCallbackResults(): void
    {
        $callbackBackends = [];
        $sequence = Recurrence::of(
            [Number::of(1), Number::of(1)],
            static function (Number $previous, Number $before) use (&$callbackBackends): Number {
                $result = $previous->add($before);
                $callbackBackends[] = [$previous->backend(), $before->backend(), $result->backend()];
                return $result;
            },
        )->offPrecision();

        $result = $sequence->at(Number::of(7));

        self::assertNotEmpty($callbackBackends);
        foreach ($callbackBackends as [$previousBackend, $beforeBackend, $resultBackend]) {
            self::assertSame('float', $previousBackend);
            self::assertSame('float', $beforeBackend);
            self::assertSame('float', $resultBackend);
        }
        self::assertSame('float', $result->backend());
        self::assertSame('13', $result->value());
    }

    public function testRecurrenceRequiresExactlyTwoInitialValues(): void
    {
        foreach ([[], [Number::of(0)], [Number::of(0), Number::of(1), Number::of(2)]] as $initialValues) {
            try {
                Recurrence::of(
                    $initialValues,
                    static fn (Number $previous, Number $before): Number => $previous->add($before),
                );
                self::fail('Expected a second-order recurrence to require exactly two initial values.');
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    public function testRecurrenceRejectsNonIntegerIndexes(): void
    {
        $sequence = Recurrence::of(
            [Number::of(0), Number::of(1)],
            static fn (Number $previous, Number $before): Number => $previous->add($before),
        );

        $this->expectException(InvalidArgumentException::class);
        $sequence->at(Number::of('2.5'));
    }

    public function testGeometricSequenceRejectsExponentsOutsideNumberPowRange(): void
    {
        $sequence = GeometricSequence::from(Number::of(2), Number::of(3));

        $this->expectException(InvalidArgumentException::class);
        $sequence->at(Number::of('100000000000000000000'));
    }
}
