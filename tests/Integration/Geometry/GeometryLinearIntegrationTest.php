<?php

declare(strict_types=1);

namespace Gauss\Tests\Integration\Geometry;

use Gauss\Geometry\Point;
use Gauss\Linear\Vector;
use Gauss\Number\Number;
use PHPUnit\Framework\TestCase;

final class GeometryLinearIntegrationTest extends TestCase
{
    public function testItUsesLinearVectorsAsPointCoordinatesAndTranslation(): void
    {
        $origin = Point::of(0, 0);
        $translated = $origin->translate(Vector::of(3, 4));
        $distance = $origin->distanceTo($translated);

        self::assertInstanceOf(Number::class, $distance);
        self::assertSame(0, $distance->compare(5));
        self::assertSame(0, $translated->coordinates()->get(1)->compare(4));
    }
}