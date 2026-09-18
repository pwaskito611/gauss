<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Discrete\Set;

use Gauss\Discrete\Set\Relation;
use Gauss\Discrete\Set\Set;
use Gauss\Number\Number;
use PHPUnit\Framework\TestCase;

final class SetTest extends TestCase
{
    public function testSetOperations(): void
    {
        $a = Set::of([Number::of(1), Number::of(2), Number::of(3)]);
        $b = Set::of([Number::of(2), Number::of(3), Number::of(4)]);

        self::assertSame(4, $a->union($b)->count());
        self::assertTrue($a->contains(Number::of(2)));
        self::assertTrue($a->intersection($b)->contains(Number::of(2)));
        self::assertTrue($a->difference($b)->contains(Number::of(1)));
    }

    public function testRelationTracksPairs(): void
    {
        $relation = Relation::of([
            [Number::of(1), Number::of(2)],
            [Number::of(2), Number::of(4)],
        ]);

        self::assertTrue($relation->contains([Number::of(1), Number::of(2)]));
    }
}
