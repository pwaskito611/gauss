<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Probability;

use DivisionByZeroError;
use Gauss\Number\Number;
use Gauss\Probability\ConditionalProbability;
use Gauss\Probability\Event;
use Gauss\Probability\Expectation;
use Gauss\Probability\Probability;
use Gauss\Probability\ProbabilityMeasure;
use Gauss\Probability\RandomVariable;
use Gauss\Probability\SampleSpace;
use Gauss\Probability\Variance;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ProbabilityTest extends TestCase
{
    public function testProbabilityValueValidation(): void
    {
        self::assertSame('0.5', Probability::of('0.5')->value());
        self::assertSame('0', Probability::of('1')->complement()->value());
        self::assertSame('0.5', Probability::of(Number::of(1)->div(3))->add(Probability::of(Number::of(1)->div(6)))->value());
        self::assertSame('0.1666666666666666666666666666666666666666666666666666666666665', Probability::of('0.5')->multiply(Probability::of(Number::of(1)->div(3)))->value());

        $this->expectException(InvalidArgumentException::class);
        Probability::of('1.5');
    }

    public function testSampleSpaceAndEventAlgebra(): void
    {
        $space = SampleSpace::of('H', 'T');
        $heads = Event::of($space, 'H');
        $tails = Event::of($space, 'T');
        $all = Event::all($space);
        $empty = Event::empty($space);

        self::assertTrue($space->contains('H'));
        self::assertTrue($heads->contains('H'));
        self::assertTrue($heads->union($tails)->equals($all));
        self::assertTrue($heads->intersection($tails)->equals($empty));
        self::assertTrue($heads->complement()->equals($tails));
        self::assertSame(2, $space->size());
    }

    public function testProbabilityMeasureAndConditionalProbability(): void
    {
        $space = SampleSpace::of(1, 2, 3, 4, 5, 6);
        $even = Event::of($space, 2, 4, 6);
        $greaterThanThree = Event::of($space, 4, 5, 6);
        $measure = ProbabilityMeasure::uniform($space);

        self::assertProbabilityApproximately('0.5', $measure->probabilityOf($even));
        self::assertProbabilityApproximately('0.5', $measure->probabilityOf($greaterThanThree));
        self::assertProbabilityApproximately('0.666666666666666666666666666666666666666666666666666666666667', $measure->conditional($even, $greaterThanThree));

        $conditional = ConditionalProbability::of($even, $greaterThanThree, $measure);
        self::assertProbabilityApproximately('0.666666666666666666666666666666666666666666666666666666666667', $conditional->value());
    }

    public function testIndependenceAndExpectationVariance(): void
    {
        $space = SampleSpace::of(1, 2, 3, 4);
        $firstTwo = Event::of($space, 1, 2);
        $odd = Event::of($space, 1, 3);
        $measure = ProbabilityMeasure::uniform($space);

        self::assertTrue($measure->areIndependent($firstTwo, $odd));

        $x = RandomVariable::of($space, [
            1 => '1',
            2 => '2',
            3 => '3',
            4 => '4',
        ]);

        self::assertSame('2.5', Expectation::of($x, $measure)->value()->value());
        self::assertSame('1.25', Variance::of($x, $measure)->value()->value());
    }

    public function testConditionalProbabilityRejectsZeroDenominator(): void
    {
        $space = SampleSpace::of('A', 'B');
        $event = Event::of($space, 'A');
        $empty = Event::empty($space);
        $measure = ProbabilityMeasure::of($space, [
            'A' => Number::of(1)->div(2),
            'B' => Number::of(1)->div(2),
        ]);

        $this->expectException(DivisionByZeroError::class);
        $measure->conditional($event, $empty);
    }

    private static function assertProbabilityApproximately(string $expected, Probability $actual): void
    {
        self::assertLessThanOrEqual(0, Number::of($actual->value())->sub($expected)->abs()->compare('0.000000000001'));
    }
}
