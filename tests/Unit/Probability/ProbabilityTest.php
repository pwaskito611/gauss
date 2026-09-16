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
        self::assertSame('1/2', Probability::of('1/2')->value());
        self::assertSame('0', Probability::of('1')->complement()->value());
        self::assertSame('1/2', Probability::of('1/3')->add(Probability::of('1/6'))->value());
        self::assertSame('1/6', Probability::of('1/2')->multiply(Probability::of('1/3'))->value());

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

        self::assertSame('1/2', $measure->probabilityOf($even)->value());
        self::assertSame('1/2', $measure->probabilityOf($greaterThanThree)->value());
        self::assertSame('2/3', $measure->conditional($even, $greaterThanThree)->value());

        $conditional = ConditionalProbability::of($even, $greaterThanThree, $measure);
        self::assertSame('2/3', $conditional->value()->value());
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

        self::assertSame('5/2', Expectation::of($x, $measure)->value()->value());
        self::assertSame('5/4', Variance::of($x, $measure)->value()->value());
    }

    public function testConditionalProbabilityRejectsZeroDenominator(): void
    {
        $space = SampleSpace::of('A', 'B');
        $event = Event::of($space, 'A');
        $empty = Event::empty($space);
        $measure = ProbabilityMeasure::of($space, [
            'A' => '1/2',
            'B' => '1/2',
        ]);

        $this->expectException(DivisionByZeroError::class);
        $measure->conditional($event, $empty);
    }
}
