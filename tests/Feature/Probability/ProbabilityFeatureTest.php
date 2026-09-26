<?php

declare(strict_types=1);

namespace Gauss\Tests\Feature\Probability;

use Gauss\Number\Number;
use Gauss\Probability\Event;
use Gauss\Probability\Probability;
use Gauss\Probability\ProbabilityMeasure;
use Gauss\Probability\SampleSpace;
use PHPUnit\Framework\TestCase;

final class ProbabilityFeatureTest extends TestCase
{
    public function testItComputesEventAndConditionalProbabilities(): void
    {
        $space = SampleSpace::of('HH', 'HT', 'TH', 'TT');
        $measure = ProbabilityMeasure::uniform($space);
        $atLeastOneHead = Event::of($space, 'HH', 'HT', 'TH');
        $firstFlipHead = Event::of($space, 'HH', 'HT');

        self::assertSame(0, $measure->probabilityOf($atLeastOneHead)->compare('0.75'));
        self::assertSame(0, $measure->conditional($atLeastOneHead, $firstFlipHead)->compare('1'));
        self::assertTrue($measure->areIndependent(
            Event::of($space, 'HH', 'HT'),
            Event::of($space, 'HH', 'TH'),
        ));
        $probability = $measure->probabilityOf($atLeastOneHead);
        self::assertInstanceOf(Probability::class, $probability);
        self::assertInstanceOf(Number::class, $probability->value());
        self::assertSame(0, $probability->value()->compare('0.75'));
    }
}