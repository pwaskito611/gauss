<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Geometry;

use Gauss\Geometry\Circle;
use Gauss\Geometry\Line;
use Gauss\Geometry\Point;
use Gauss\Geometry\Segment;
use Gauss\Linear\Vector;
use Gauss\Number\Number;
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
