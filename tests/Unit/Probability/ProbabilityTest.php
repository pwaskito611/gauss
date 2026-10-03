<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Probability;

use DivisionByZeroError;
use Gauss\Number\Number;
use Gauss\Probability\ConditionalProbability;
use Gauss\Probability\Event;
use Gauss\Probability\Expectation;
use Gauss\Probability\OutcomeIdentity;
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
        self::assertSame('0.5', Probability::of('0.5')->value()->value());
        self::assertSame('0', Probability::of('1')->complement()->value()->value());
        self::assertSame('0.5', Probability::of(Number::of(1)->div(3))->add(Probability::of(Number::of(1)->div(6)))->value()->value());
        self::assertSame('0.1666666666666666666666666666666666666666666666666666666666665', Probability::of('0.5')->multiply(Probability::of(Number::of(1)->div(3)))->value()->value());

        $this->expectException(InvalidArgumentException::class);
        Probability::of('1.5');
    }

    public function testProbabilityFactoryAndMeasureAcceptProbabilityValues(): void
    {
        $probability = Probability::of('0.5');
        self::assertSame($probability, Probability::of($probability));

        $measure = ProbabilityMeasure::of(SampleSpace::of('A', 'B'), [$probability, $probability]);
        self::assertTrue($measure->probabilityFor('A')->compare('0.5') === 0);
    }

    public function testProbabilityOperationsPreserveInvariant(): void
    {
        self::assertTrue(Probability::of('0.8')->add(Probability::of('0.2'))->isUnit());
        self::assertTrue(Probability::of('0.8')->multiply(Probability::of('0.5'))->compare('0.4') === 0);
        self::assertSame(0, Probability::of('0.5')->compare(Probability::of('0.5')));

        $this->expectException(InvalidArgumentException::class);
        Probability::of('0.8')->add(Probability::of('0.3'));
    }

    public function testProbabilityDivisionReturnsGeneralRatios(): void
    {
        self::assertSame(0, Probability::of('0.5')->divide(Probability::of('0.5'))->compare(1));
        self::assertSame(0, Probability::of('0.2')->divide(Probability::of('0.5'))->compare('0.4'));
        self::assertSame(0, Probability::of('0.8')->divide(Probability::of('0.5'))->compare('1.6'));
    }

    public function testProbabilityDivisionByZeroIsRejected(): void
    {
        $this->expectException(DivisionByZeroError::class);
        Probability::of('0.5')->divide(Probability::of('0'));
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

        $x = RandomVariable::fromMap($space, [
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
        $measure = ProbabilityMeasure::fromMap($space, [
            'A' => Number::of(1)->div(2),
            'B' => Number::of(1)->div(2),
        ]);

        $this->expectException(DivisionByZeroError::class);
        $measure->conditional($event, $empty);
    }

    public function testOutcomeIdentityPreservesMixedTypes(): void
    {
        $space = SampleSpace::of(1, '1', 1.5, true, false, null);

        self::assertSame(6, $space->size());
        self::assertTrue($space->contains(1));
        self::assertTrue($space->contains('1'));
        self::assertTrue($space->contains(1.5));
        self::assertTrue($space->contains(true));
        self::assertTrue($space->contains(false));
        self::assertTrue($space->contains(null));

        $measure = ProbabilityMeasure::uniform($space);
        self::assertProbabilityApproximately('0.166666666666666666666666666666666666666666666666666666666667', $measure->probabilityOf(Event::of($space, '1')));

        $variable = RandomVariable::of($space, [10, 20, 30, 40, 50, 60]);
        self::assertSame('10', $variable->valueFor(1)->value());
        self::assertSame('20', $variable->valueFor('1')->value());
    }

    public function testProbabilityWeightsMustSumExactlyToOne(): void
    {
        $space = SampleSpace::of('A', 'B');

        $this->expectException(InvalidArgumentException::class);
        ProbabilityMeasure::of($space, ['A' => '0.6', 'B' => '0.5']);
    }

    public function testProbabilityMeasureOfIsPositionalOnly(): void
    {
        $space = SampleSpace::of(1, 0);
        $measure = ProbabilityMeasure::of($space, [0 => '0.2', 1 => '0.8']);

        self::assertProbabilityApproximately('0.2', $measure->probabilityOf(Event::of($space, 1)));
        self::assertProbabilityApproximately('0.8', $measure->probabilityOf(Event::of($space, 0)));
    }

    public function testFromMapRejectsIncompleteAndUnknownOutcomes(): void
    {
        $space = SampleSpace::of('A', 'B', 'C');

        $this->expectException(InvalidArgumentException::class);
        ProbabilityMeasure::fromMap($space, ['A' => '0.2', 'B' => '0.8']);
    }

    public function testFromMapRejectsUnknownOutcome(): void
    {
        $space = SampleSpace::of('A', 'B');

        $this->expectException(InvalidArgumentException::class);
        ProbabilityMeasure::fromMap($space, ['A' => '0.5', 'B' => '0.4', 'C' => '0.1']);
    }

    public function testFromMapRejectsDuplicateOutcomePairs(): void
    {
        $space = SampleSpace::of('A', 'B');

        $this->expectException(InvalidArgumentException::class);
        ProbabilityMeasure::fromMap($space, [
            ['A', '0.5'],
            ['A', '0.25'],
            ['B', '0.25'],
        ]);
    }

    public function testExplicitMapSupportsIntegerAndObjectOutcomes(): void
    {
        $integerSpace = SampleSpace::of(1, 0);
        $integerMeasure = ProbabilityMeasure::fromMap($integerSpace, [0 => '0.2', 1 => '0.8']);
        self::assertProbabilityApproximately('0.8', $integerMeasure->probabilityOf(Event::of($integerSpace, 1)));
        self::assertProbabilityApproximately('0.2', $integerMeasure->probabilityOf(Event::of($integerSpace, 0)));

        $first = new \stdClass();
        $first->value = 1;
        $second = new \stdClass();
        $second->value = 1;
        $objectSpace = SampleSpace::of($first, $second);
        $measure = ProbabilityMeasure::fromMap($objectSpace, [
            [$first, '0.25'],
            [$second, '0.75'],
        ]);

        self::assertSame(2, $objectSpace->size());
        self::assertProbabilityApproximately('0.25', $measure->probabilityOf(Event::of($objectSpace, $first)));
        self::assertProbabilityApproximately('0.75', $measure->probabilityOf(Event::of($objectSpace, $second)));
    }

    public function testEventIdentityPreventsDoubleCounting(): void
    {
        $first = 1.0;
        $second = 1.0000000000000002;
        self::assertNotSame(OutcomeIdentity::key($first), OutcomeIdentity::key($second));

        $space = SampleSpace::of($first, $second);
        $measure = ProbabilityMeasure::fromMap($space, [
            [$first, '0.4'],
            [$second, '0.6'],
        ]);

        self::assertCount(2, Event::all($space)->outcomes());
        self::assertTrue($measure->probabilityOf(Event::all($space))->isUnit());
    }

    public function testOutcomeIdentityIsTypeSensitiveForIntegerAndFloat(): void
    {
        $space = SampleSpace::of(1, '1', true, 1.0);

        self::assertSame(4, $space->size());
        self::assertNotSame(OutcomeIdentity::key(1), OutcomeIdentity::key('1'));
        self::assertNotSame(OutcomeIdentity::key(1), OutcomeIdentity::key(true));
        self::assertNotSame(OutcomeIdentity::key(1), OutcomeIdentity::key(1.0));
    }

    public function testRandomVariableRejectsDuplicateSourceOutcomes(): void
    {
        $space = SampleSpace::of('A', 'B');

        $this->expectException(InvalidArgumentException::class);
        RandomVariable::fromMap($space, [
            ['A', 10],
            ['A', 20],
            ['B', 30],
        ]);
    }

    public function testRandomVariableMappingIsAlwaysPositional(): void
    {
        $space = SampleSpace::of('A', 'B');
        $variable = RandomVariable::fromMap($space, [
            ['A', 10],
            ['B', 20],
        ]);

        self::assertSame(['10', '20'], array_map(
            static fn (Number $value): string => $value->value(),
            $variable->mapping()
        ));
    }

    public function testRandomVariableOfRejectsAssociativeMapping(): void
    {
        $space = SampleSpace::of(1, 0);

        $this->expectException(InvalidArgumentException::class);
        RandomVariable::of($space, [1 => 10, 0 => 20]);
    }

    public function testNanIsRejectedAsSampleSpaceOutcome(): void
    {
        $space = SampleSpace::of('A');
        self::assertFalse($space->contains(NAN));
        self::assertFalse(Event::empty($space)->contains(NAN));

        try {
            Event::of($space, NAN);
            self::fail('Expected NAN event outcome to be rejected.');
        } catch (InvalidArgumentException) {
            self::assertTrue(true);
        }

        $this->expectException(InvalidArgumentException::class);
        SampleSpace::of(NAN);
    }

    public function testCircularArrayOutcomeIsRejected(): void
    {
        $circular = [];
        $circular['self'] =& $circular;

        $this->expectException(InvalidArgumentException::class);
        OutcomeIdentity::key($circular);
    }

    public function testUniformMeasureNormalizesForDifferentSpaceSizes(): void
    {
        foreach ([2, 3, 7, 10, 100] as $size) {
            $space = SampleSpace::of(...range(1, $size));
            self::assertTrue(ProbabilityMeasure::uniform($space)->probabilityOf(Event::all($space))->isUnit());
        }
    }

    public function testEventEqualityIncludesSampleSpaceForEmptyEvents(): void
    {
        $first = Event::empty(SampleSpace::of(1, 2));
        $second = Event::empty(SampleSpace::of(3, 4));
        $same = Event::empty(SampleSpace::of(1, 2));

        self::assertFalse($first->equals($second));
        self::assertTrue($first->equals($same));
    }

    public function testEventSetOperationsUseStrictOutcomeIdentity(): void
    {
        $space = SampleSpace::of(1, '1', 2);
        $first = Event::of($space, 1, 2);
        $second = Event::of($space, '1', 2);

        self::assertSame([1, 2, '1'], $first->union($second)->outcomes());
        self::assertSame([2], $first->intersection($second)->outcomes());
        self::assertSame([1], $first->difference($second)->outcomes());
        self::assertSame(['1'], $first->complement()->outcomes());
    }

    public function testOutcomeIdentityRejectsArrayReferencesRecursively(): void
    {
        $value = 1;
        $outcome = [
            'nested' => [
                'value' => &$value,
            ],
        ];

        $this->expectException(InvalidArgumentException::class);
        OutcomeIdentity::key($outcome);
    }

    public function testCriticalNestedArrayCollisionIsRejectedAtSampleSpaceLevel(): void
    {
        $first = [0 => [0 => 1, 1 => 2]];
        $second = [
            0 => [0 => 1],
            1 => 2,
        ];

        self::assertNotSame(OutcomeIdentity::key($first), OutcomeIdentity::key($second));

        $space = SampleSpace::of($first, $second);
        self::assertSame(2, $space->size());
        self::assertTrue($space->contains($first));
        self::assertTrue($space->contains($second));
    }

    public function testSampleSpaceMismatchFailsExplicitlyForExpectationAndVariance(): void
    {
        $spaceOne = SampleSpace::of(1, 2);
        $spaceTwo = SampleSpace::of(1, 2, 3);
        $variable = RandomVariable::of($spaceOne, [10, 20]);
        $measure = ProbabilityMeasure::uniform($spaceTwo);

        foreach ([Expectation::class, Variance::class] as $factory) {
            try {
                $factory::of($variable, $measure);
                self::fail('Expected sample-space mismatch to be rejected.');
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    private static function assertProbabilityApproximately(string $expected, Probability $actual): void
    {
        self::assertLessThanOrEqual(0, Number::of($actual->value())->sub($expected)->abs()->compare('0.000000000001'));
    }
}
