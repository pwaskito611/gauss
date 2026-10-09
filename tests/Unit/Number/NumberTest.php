<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Number;

use DivisionByZeroError;
use Gauss\Number\Decimal;
use Gauss\Number\Number;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

final class NumberTest extends TestCase
{
    public function testConstants(): void
    {
        self::assertSame(
            '3.14159265358979323846264338327950288419716939937511',
            Number::pi()->value()
        );

        self::assertSame(
            '2.71828182845904523536028747135266249775724709369996',
            Number::e()->value()
        );
    }

    public function testOf(): void
    {
        self::assertSame('123', Number::of(123)->value());
        self::assertSame('1.5', Number::of(1.5)->value());
        self::assertSame('1.23', Number::of('001.2300')->value());

        $number = Number::of('1.23');
        self::assertSame($number, Number::of($number));
    }

    public function testOffPrecisionUsesFloatBackendAndPropagates(): void
    {
        $number = Number::of('0.1')->offPrecision();

        self::assertSame('float', $number->backend());
        self::assertSame('0.30000000000000004', $number->add('0.2')->value());
        self::assertSame('0.5', $number->div(0.2)->value());
        self::assertSame('bcmath', Number::of('0.1')->backend());
        self::assertSame('bcmath', Number::of('0.1')->add('0.2')->backend());
        self::assertSame('1', Number::of(10)->offPrecision()->mod(3)->value());
        self::assertSame('2', Number::of(-10)->offPrecision()->mod(3)->value());
    }

    public function testOffPrecisionRejectsInvalidFloatRangeAndPreservesDomainErrors(): void
    {
        try {
            Number::of('1e400')->offPrecision();
            self::fail('Expected an out-of-range value to be rejected.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('float range', $exception->getMessage());
        }

        try {
            Number::of('1e-400')->offPrecision();
            self::fail('Expected an underflowing value to be rejected.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('underflows', $exception->getMessage());
        }

        $this->expectException(DivisionByZeroError::class);
        Number::of(0)->offPrecision()->pow(-1);
    }

    public function testOfRejectsInvalidString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Number::of('abc');
    }

    public function testOfRejectsInfinity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Number::of(INF);
    }

    public function testOne(): void
    {
        self::assertSame('1', Number::of(5)->one()->value());
    }

    public function testAddSubMul(): void
    {
        self::assertSame('0.3', Number::of('0.1')->add('0.2')->value());
        self::assertSame('5.73', Number::of('1.23')->add('4.5')->value());

        self::assertSame('0.001', Number::of('1')->sub('0.999')->value());

        self::assertSame('0.02', Number::of('0.1')->mul('0.2')->value());
        self::assertSame('3', Number::of('1.5')->mul(2)->value());
    }

    public function testFiniteDecimalArithmeticInvariants(): void
    {
        foreach ([
            ['1.234', '5.678'],
            ['-2.75', '0.125'],
            ['100000000000000001', '0.0001'],
        ] as [$left, $right]) {
            $a = Number::of($left);
            $b = Number::of($right);

            self::assertSame(0, $a->add($b)->compare($b->add($a)));
            self::assertSame(0, $a->add($b)->sub($b)->compare($a));
            self::assertSame(0, $a->mul($b)->compare($b->mul($a)));
        }
    }

    public function testDiv(): void
    {
        self::assertSame('0.5', Number::of(1)->div(2)->value());
        self::assertSame('0.125', Number::of(1)->div(8)->value());
        self::assertSame(
            '0.' . str_repeat('3', 60),
            Number::of(1)->div(3)->value()
        );
        self::assertSame(
            '-0.' . str_repeat('3', 60),
            Number::of(-1)->div(3)->value()
        );
        self::assertSame('2.5', Number::of(5)->div(2)->value());
    }

    public function testDivByZero(): void
    {
        $this->expectException(DivisionByZeroError::class);
        Number::of(1)->div(0);
    }

    public function testDivVerySmall(): void
    {
        $expected = '0.' . str_repeat('0', 99) . '1';

        self::assertSame($expected, Number::of(1)->div('1e100')->value());
        self::assertSame('-' . $expected, Number::of(-1)->div('1e100')->value());
        self::assertSame('-' . $expected, Number::of(1)->div('-1e100')->value());
        self::assertSame($expected, Number::of('1e-100')->div(1)->value());
        self::assertSame(
            '0.' . str_repeat('0', 19999) . '1',
            Number::of('1e-10000')->div('1e10000')->value()
        );
    }

    public function testMod(): void
    {
        self::assertSame('1', Number::of(10)->mod(3)->value());
        self::assertSame('2', Number::of(-10)->mod(3)->value());
        self::assertSame('1', Number::of(10)->mod(-3)->value());
        self::assertSame('2', Number::of(-10)->mod(-3)->value());
        self::assertSame('2', Number::of(5)->mod(3)->value());
        self::assertSame('1', Number::of(-5)->mod(3)->value());
        self::assertSame('2', Number::of(5)->mod(-3)->value());
        self::assertSame('1', Number::of(-5)->mod(-3)->value());

        foreach ([
            [5, 3],
            [-5, 3],
            [5, -3],
            [-5, -3],
        ] as [$dividend, $divisor]) {
            $remainder = Number::of($dividend)->mod($divisor);

            self::assertGreaterThanOrEqual(0, $remainder->compare(0));
            self::assertLessThan(0, $remainder->compare(abs($divisor)));
        }
    }

    public function testModRejectsNonInteger(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Number::of('1.5')->mod(1);
    }

    public function testModByZero(): void
    {
        $this->expectException(DivisionByZeroError::class);
        Number::of(1)->mod(0);
    }

    public function testCompare(): void
    {
        self::assertSame(0, Number::of('1.0')->compare('1'));
        self::assertSame(1, Number::of(2)->compare(1));
        self::assertSame(-1, Number::of(1)->compare(2));
        self::assertSame(1, Number::of('1e-100')->compare('0'));
    }

    public function testAbs(): void
    {
        self::assertSame('1.23', Number::of('-1.23')->abs()->value());

        $number = Number::of('1.23');
        self::assertSame($number, $number->abs());
    }

    public function testPow(): void
    {
        self::assertSame('1024', Number::of(2)->pow(10)->value());
        self::assertSame('1', Number::of(2)->pow(0)->value());
        self::assertSame('0.25', Number::of(2)->pow(-2)->value());
        self::assertSame('2.25', Number::of('1.5')->pow(2)->value());
        self::assertSame('15.625', Number::of('2.5')->pow(3)->value());
        self::assertSame('9536.7431640625', Number::of('2.5')->pow(10)->value());
        self::assertSame('0.0000000001', Number::of('0.1')->pow(10)->value());
        self::assertSame('1', Number::of(0)->pow(0)->value());
        self::assertSame('0', Number::of(0)->pow(5)->value());
        self::assertSame('1', Number::of(1)->pow(10000)->value());
        self::assertSame('1', Number::of(-1)->pow(-2)->value());
        self::assertSame('-1', Number::of(-1)->pow(-3)->value());
    }

    public function testPowRejectsOutOfRangeExponent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Number::of(2)->pow(10001);
    }

    public function testSqrt(): void
    {
        self::assertSame('2', Number::of(4)->sqrt()->value());
        self::assertSame('1', Number::of(1)->sqrt()->value());
        self::assertSame('0.5', Number::of('0.25')->sqrt()->value());
        self::assertSame(
            '0.' . str_repeat('0', 49) . '1',
            Number::of('1e-100')->sqrt()->value()
        );

        self::assertSame(
            '1.41421356237309504880168872420969807856967187537695',
            Number::of(2)->sqrt()->value()
        );

        $root = Number::of(2)->sqrt();
        self::assertLessThanOrEqual(
            0,
            $root->mul($root)->sub(2)->abs()->compare('1e-49')
        );

        self::assertSame('0', Number::of(0)->sqrt()->value());
    }

    public function testSqrtRejectsNegative(): void
    {
        $this->expectException(LogicException::class);
        Number::of(-1)->sqrt();
    }

    public function testExp(): void
    {
        self::assertSame('1', Number::of(0)->exp()->value());
        self::assertSame(Number::e()->value(), Number::of(1)->exp()->value());

        $expNeg1 = Number::of(-1)->exp();

        self::assertStringStartsWith(
            '0.367879441171442321595523770161460867445811131031',
            $expNeg1->value()
        );

        self::assertSame(1, $expNeg1->compare(0));
        self::assertSame(-1, $expNeg1->compare(1));
    }

    public function testExpPreservesTinyPositiveAndNegativeTerms(): void
    {
        $small = '0.' . str_repeat('0', 39) . '1';

        self::assertSame(
            '1.' . str_repeat('0', 39) . '1',
            Number::of($small)->exp()->value()
        );
        self::assertSame(
            '0.' . str_repeat('9', 40),
            Number::of('-' . $small)->exp()->value()
        );
    }

    public function testExpAgainstHighPrecisionDecimalReferences(): void
    {
        $references = [
            '-1000' => '5.075958897549456765291809479574336919305599282892837361832393845410540542974819175679662169046542868E-435',
            '-100' => '3.720075976020835962959695803863118337358892292376781967120613876663290475895815718157118778642281497E-44',
            '-50' => '1.928749847963917783017342816527012574752832651230262910897809103820511624979646591652373378777735137E-22',
            '-10' => '0.00004539992976248485153559151556055061023791808886656496925907130565099942161430228165252500454594778232',
            '-1' => '0.3678794411714423215955237701614608674458111310317678345078368016974614957448998033571472743459196437',
            '-0.1' => '0.9048374180359595731642490594464366211947053609804',
            '0' => '1',
            '0.1' => '1.10517091807564762481170782649024666822454719473752',
            '1' => '2.718281828459045235360287471352662497757247093699959574966967627724076630353547594571382178525166427',
            '2' => '7.389056098930650227230427460575007813180315570551847324087127822522573796079057763384312485079121795',
            '10' => '22026.46579480671651695790064528424436635351261855678107423542635522520281857079257519912096816452590',
            '50' => '5184705528587072464087.453322933485384827469100583846401904056933806856884793795398480090388704093567',
            '100' => '26881171418161354484126255515800135873611118.77374192241519160861528028703490956491415887109722',
            '116' => '238869060142499142546263929494416116606198129645646.87296929335444290357575123831933376172389974880642',
            '120' => '13041808783936322797338790280986488113446079415755132.72831442152669685506218043075882881125113979282266',
            '130' => '287264955081783193326733322496215381894532426973996326913.13900047927863039809853023718801261500563942279373',
        ];
        $tolerance = Number::of('5e-51');

        foreach ($references as $input => $reference) {
            $error = Number::of($input)->exp()
                ->sub($reference)
                ->abs();

            self::assertLessThanOrEqual(
                0,
                $error->compare($tolerance),
                "exp({$input}) exceeded the SCALE rounding error bound."
            );
        }
    }

    public function testExpCompositionWithinRoundingTolerance(): void
    {
        $composed = Number::of('0.25')->exp()
            ->mul(Number::of('0.75')->exp());
        $direct = Number::of(1)->exp();

        self::assertLessThanOrEqual(
            0,
            $composed->sub($direct)->abs()->compare('1e-48')
        );
    }

    public function testExpNegativeValuesRoundToZeroAtProvenBoundary(): void
    {
        self::assertSame('0', Number::of('-129.99')->exp()->value());
        self::assertSame('0', Number::of(-130)->exp()->value());
        self::assertSame('0', Number::of(-120)->exp()->value());
        self::assertSame('0', Number::of(-116)->exp()->value());
        self::assertSame(
            '0.' . str_repeat('0', 49) . '1',
            Number::of(-115)->exp()->value()
        );
        self::assertSame('0', Number::of(-1000)->exp()->value());
        self::assertSame('0', Number::of(-10000)->exp()->value());
        self::assertSame(4394, strlen(Number::of(10000)->exp()->value()));
    }

    public function testExpCertifiesNearHalfwayRoundingAfterRetry(): void
    {
        $coefficient = bcsub('5' . str_repeat('0', 52), '125', 0);
        $input = '0.' . str_pad($coefficient, 103, '0', STR_PAD_LEFT);
        $initialEnclosure = new \ReflectionMethod(
            Number::class,
            'positiveExpEnclosure'
        );
        $initialEnclosure->setAccessible(true);
        [$lower, $upper] = $initialEnclosure->invoke(null, $input, 1, 104);

        self::assertNotSame(
            Decimal::round($lower, 50),
            Decimal::round($upper, 50)
        );
        self::assertSame('1', Number::of($input)->exp()->value());
    }

    public function testExpRejectsOutOfRangeArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Number::of(10001)->exp();
    }

    public function testExpRejectsFractionAboveMaximumArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Number::of('10000.000000000000000001')->exp();
    }

    public function testExpRejectsNegativeOutOfRangeArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Number::of(-10001)->exp();
    }

    public function testTypeAndStringRepresentation(): void
    {
        $integer = Number::of('123');
        self::assertSame('integer', $integer->type());
        self::assertTrue($integer->isIntegerLike());
        self::assertFalse($integer->isDecimalLike());

        $decimal = Number::of('123.45');
        self::assertSame('decimal', $decimal->type());
        self::assertFalse($decimal->isIntegerLike());
        self::assertTrue($decimal->isDecimalLike());
        self::assertSame('123.45', (string) $decimal);

        $normalized = Number::of('123.00');
        self::assertSame('123', $normalized->value());
        self::assertSame('integer', $normalized->type());
    }
}