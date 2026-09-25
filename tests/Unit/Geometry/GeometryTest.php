<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Geometry;

use Gauss\Geometry\Circle;
use Gauss\Geometry\Line;
use Gauss\Geometry\Point;
use Gauss\Geometry\Segment;
use Gauss\Linear\Vector;
use Gauss\Number\Number;
use LogicException;
use PHPUnit\Framework\TestCase;

final class GeometryTest extends TestCase
{
    public function testPointGeometryOperationsAreComposable(): void
    {
        $origin = Point::of(0, 0);
        $target = Point::of(3, 4);

        self::assertSame('5', $origin->distanceTo($target)->value());
        self::assertSame(['3', '4'], $this->vectorValues($origin->vectorTo($target)));
        self::assertSame(['1.5', '2'], $this->pointValues($origin->midpoint($target)));

        $translated = $origin->translate(Vector::of(1, 2));
        self::assertSame(['1', '2'], $this->pointValues($translated));
    }

    public function testLineContainsProjectedPointAndDistance(): void
    {
        $line = Line::through(Point::of(0, 0), Vector::of(1, 0));

        self::assertTrue($line->contains(Point::of(4, 0)));
        self::assertSame(['4', '0'], $this->pointValues($line->project(Point::of(4, 3))));
        self::assertSame('3', $line->distanceTo(Point::of(4, 3))->value());
    }

    public function testSegmentLengthAndMidpoint(): void
    {
        $segment = Segment::between(Point::of(0, 0), Point::of(6, 8));

        self::assertSame('10', $segment->length()->value());
        self::assertSame(['3', '4'], $this->pointValues($segment->midpoint()));
    }

    public function testCircleAreaCircumferenceAndContainment(): void
    {
        $circle = Circle::of(Point::of(0, 0), Number::of(3));

        self::assertSame('28.27433388230813914616379044951552595777452459437599', $circle->area()->value());
        self::assertSame('18.84955592153875943077586029967701730518301639625066', $circle->circumference()->value());
        self::assertTrue($circle->contains(Point::of(2, 0)));
        self::assertFalse($circle->contains(Point::of(4, 0)));
    }

    public function testCompositionChainAcrossGeometricPrimitives(): void
    {
        $pointA = Point::of(0, 0);
        $pointB = Point::of(3, 4);

        $result = $pointA
            ->vectorTo($pointB)
            ->normalize()
            ->scale(Number::of('0.5'))
            ->map(static fn ($value) => $value->mul(Number::of(2)));

        self::assertSame(['0.6', '0.8'], $this->vectorValues($result));
    }

    public function testPointEqualityUsesNumericCoordinatesAndDimensions(): void
    {
        self::assertTrue(Point::of('0.0', '2.00')->equals(Point::of(0, 2)));
        self::assertTrue(Point::of(1, 2)->equals(Point::of(1, 2)));
        self::assertFalse(Point::of(1, 2)->equals(Point::of(1, 3)));
        self::assertFalse(Point::of(1, 2)->equals(Point::of(1, 2, 0)));
    }

    public function testMidpointSatisfiesTheDoubleMidpointInvariant(): void
    {
        $left = Point::of(1, 4);
        $right = Point::of(5, 8);
        $midpoint = $left->midpoint($right);
        $one = $midpoint->coordinates()->get(0)->one();
        $doubled = $midpoint->coordinates()->scale($one->add($one));

        self::assertTrue($doubled->get(0)->compare($left->coordinates()->get(0)->add($right->coordinates()->get(0))) === 0);
        self::assertTrue($doubled->get(1)->compare($left->coordinates()->get(1)->add($right->coordinates()->get(1))) === 0);
    }

    public function testProjectionResidualIsOrthogonalToLineDirection(): void
    {
        $line = Line::through(Point::of(0, 0), Vector::of(1, 1));
        $point = Point::of(3, 0);
        $projection = $line->project($point);

        self::assertSame('1.5', $projection->coordinates()->get(0)->value());
        self::assertSame('1.5', $projection->coordinates()->get(1)->value());
        self::assertSame(0, $point->coordinates()->sub($projection->coordinates())->dot($line->direction())->compare(0));
    }

    public function testLineIntersectionBelongsToBothLines(): void
    {
        $first = Line::through(Point::of(0, 0), Vector::of(1, 1));
        $second = Line::through(Point::of(0, 2), Vector::of(1, -1));
        $intersection = $first->intersectionWith($second);

        self::assertTrue($first->contains($intersection));
        self::assertTrue($second->contains($intersection));
        self::assertTrue($intersection->equals(Point::of(1, 1)));
    }

    public function testParallelLinesHaveExplicitFailure(): void
    {
        $line = Line::through(Point::of(0, 0), Vector::of(1, 1));
        $parallel = Line::through(Point::of(0, 1), Vector::of(2, 2));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('parallel (or coincident)');
        $line->intersectionWith($parallel);
    }

    public function testCoincidentLinesHaveExplicitFailure(): void
    {
        $line = Line::through(Point::of(0, 0), Vector::of(1, 1));
        $coincident = Line::through(Point::of(3, 3), Vector::of(2, 2));

        try {
            $line->intersectionWith($coincident);
            self::fail('Expected coincident lines to have no unique intersection.');
        } catch (LogicException $exception) {
            self::assertStringContainsString('parallel (or coincident)', $exception->getMessage());
        }
    }

    public function testDegenerateSegmentContainsOnlyItsEndpoint(): void
    {
        $point = Point::of(2, 3);
        $segment = Segment::between($point, Point::of('2.0', '3.0'));

        self::assertTrue($segment->contains(Point::of(2, 3)));
        self::assertFalse($segment->contains(Point::of(2, 4)));
    }

    public function testCircleContainsBoundaryButNotExterior(): void
    {
        $circle = Circle::of(Point::of(0, 0), 3);

        self::assertTrue($circle->contains(Point::of(0, 0)));
        self::assertTrue($circle->contains(Point::of(3, 0)));
        self::assertFalse($circle->contains(Point::of(4, 0)));
    }

    /** @return list<string> */
    private function pointValues(Point $point): array
    {
        return array_map(
            static fn ($value): string => $value->value(),
            $point->coordinates()->values()
        );
    }

    /** @return list<string> */
    private function vectorValues(Vector $vector): array
    {
        return array_map(
            static fn ($value): string => $value->value(),
            $vector->values()
        );
    }
}
