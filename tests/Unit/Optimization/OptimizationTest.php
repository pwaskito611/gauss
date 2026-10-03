<?php

declare(strict_types=1);

namespace Gauss\Tests\Unit\Optimization;

use Gauss\Linear\Vector;
use Gauss\Number\Number;
use Gauss\Optimization\Constraint\BoxConstraint;
use Gauss\Optimization\DerivativeFree\NelderMead;
use Gauss\Optimization\Multidimensional\CoordinateDescent;
use Gauss\Optimization\Multidimensional\GradientDescent;
use Gauss\Optimization\OneDimensional\GoldenSectionSearch;
use Gauss\Optimization\OptimizationResult;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

final class OptimizationTest extends TestCase
{
    public function testGoldenSectionSearchMinimizesQuadratic(): void
    {
        $result = GoldenSectionSearch::minimize(
            static fn (Number $x): Number => $x->pow(2),
            -5,
            5,
            '0.000001',
            200,
        );

        self::assertInstanceOf(Number::class, $result->point());
        self::assertTrue($result->converged());
        self::assertLessThanOrEqual(
            0,
            $result->point()->sub(0)->abs()->compare('0.000001')
        );
    }

    public function testGoldenSectionSearchRejectsInvalidInterval(): void
    {
        $this->expectException(InvalidArgumentException::class);

        GoldenSectionSearch::minimize(
            static fn (Number $x): Number => $x->pow(2),
            5,
            -5,
        );
    }

    public function testGoldenSectionSearchMaximizeReturnsOriginalObjectiveValue(): void
    {
        $result = GoldenSectionSearch::maximize(
            static fn (Number $x): Number => Number::of(10)->sub($x->sub(2)->pow(2)),
            -5,
            5,
            '0.000001',
            200,
        );

        self::assertTrue($result->converged());
        self::assertLessThanOrEqual(0, $result->value()->sub(10)->abs()->compare('0.00001'));
    }

    public function testGoldenSectionSearchRejectsEqualBounds(): void
    {
        $this->expectException(InvalidArgumentException::class);

        GoldenSectionSearch::minimize(static fn (Number $x): Number => $x, 2, 2);
    }

    public function testGoldenSectionSearchFindsOptimumAtIntervalBoundary(): void
    {
        $result = GoldenSectionSearch::minimize(
            static fn (Number $x): Number => $x->sub(4)->pow(2),
            -5,
            4,
            '0.000001',
            200,
        );

        self::assertTrue($result->converged());
        self::assertLessThanOrEqual(0, $result->point()->sub(4)->abs()->compare('0.00001'));
    }

    public function testGradientDescentMinimizesQuadratic(): void
    {
        $result = GradientDescent::minimize(
            static fn (Vector $x): Number => $x->get(0)->pow(2)->add($x->get(1)->pow(2)),
            Vector::of(3, -2),
            '0.1',
            '0.000001',
            1000,
        );

        self::assertInstanceOf(Vector::class, $result->point());
        self::assertTrue($result->converged());
        self::assertLessThanOrEqual(0, $result->point()->get(0)->sub(0)->abs()->compare('0.000001'));
        self::assertLessThanOrEqual(0, $result->point()->get(1)->sub(0)->abs()->compare('0.000001'));
    }

    public function testGradientDescentMaximizeReturnsOriginalObjectiveValue(): void
    {
        $result = GradientDescent::maximize(
            static fn (Vector $x): Number => Number::of(10)->sub($x->get(0)->sub(2)->pow(2)),
            Vector::of(0),
            '0.1',
            '0.000001',
            100,
        );

        self::assertTrue($result->converged());
        self::assertLessThanOrEqual(0, $result->value()->sub(10)->abs()->compare('0.00001'));
    }

    public function testGradientDescentAcceptsConfiguredGradientAndLineSearchSteps(): void
    {
        $result = GradientDescent::minimize(
            static fn (Vector $x): Number => $x->get(0)->pow(2),
            Vector::of(2),
            '0.1',
            '0.00001',
            100,
            '0.0001',
            0,
            1,
        );

        self::assertTrue($result->converged());
        self::assertLessThanOrEqual(0, $result->point()->get(0)->abs()->compare('0.00001'));
    }

    public function testGradientDescentRejectsNonPositiveGradientStep(): void
    {
        $this->expectException(InvalidArgumentException::class);

        GradientDescent::minimize(
            static fn (Vector $x): Number => $x->get(0)->pow(2),
            Vector::of(1),
            '0.1',
            '0.000001',
            10,
            0,
        );
    }

    public function testGradientDescentRejectsInvalidLineSearchInterval(): void
    {
        $this->expectException(InvalidArgumentException::class);

        GradientDescent::minimize(
            static fn (Vector $x): Number => $x->get(0)->pow(2),
            Vector::of(1),
            '0.1',
            '0.000001',
            10,
            '0.000001',
            2,
            1,
        );
    }

    public function testGradientDescentDoesNotEvaluateOutsideBounds(): void
    {
        $bounds = BoxConstraint::from(Vector::of(0), Vector::of(2));
        $evaluatedPoints = [];

        GradientDescent::minimize(
            static function (Vector $point) use (&$evaluatedPoints, $bounds): Number {
                self::assertTrue($bounds->contains($point));
                $evaluatedPoints[] = $point;

                return $point->get(0)->pow(2);
            },
            Vector::of(0),
            '0.1',
            '0.000001',
            5,
            '0.000001',
            0,
            1,
            $bounds,
        );

        self::assertNotEmpty($evaluatedPoints);
    }

    public function testCoordinateDescentMinimizesQuadratic(): void
    {
        $result = CoordinateDescent::minimize(
            static fn (Vector $x): Number => $x->get(0)->pow(2)->add($x->get(1)->pow(2)),
            Vector::of(4, -3),
            '0.000001',
            200,
            BoxConstraint::from(Vector::of(-5, -5), Vector::of(5, 5)),
        );

        self::assertInstanceOf(Vector::class, $result->point());
        self::assertTrue($result->converged());
        self::assertLessThanOrEqual(0, $result->point()->get(0)->sub(0)->abs()->compare('0.000001'));
        self::assertLessThanOrEqual(0, $result->point()->get(1)->sub(0)->abs()->compare('0.000001'));
    }

    public function testCoordinateDescentUsesExplicitBoundsBeyondTheOldSearchRange(): void
    {
        $bounds = BoxConstraint::from(Vector::of(-100), Vector::of(1200));
        $result = CoordinateDescent::minimize(
            static fn (Vector $x): Number => $x->get(0)->sub(1000)->pow(2),
            Vector::of(0),
            '0.000001',
            10,
            $bounds,
        );

        self::assertTrue($result->converged());
        self::assertLessThanOrEqual(0, $result->point()->get(0)->sub(1000)->abs()->compare('0.00001'));
        self::assertTrue($bounds->contains($result->point()));
    }

    public function testNelderMeadMinimizesQuadratic(): void
    {
        $simplex = [
            Vector::of(2, 2),
            Vector::of(3, 0),
            Vector::of(0, 3),
        ];

        $result = NelderMead::minimize(
            static fn (Vector $x): Number => $x->get(0)->pow(2)->add($x->get(1)->pow(2)),
            $simplex,
            '0.000001',
            500,
        );

        self::assertInstanceOf(Vector::class, $result->point());
        self::assertTrue($result->converged());
        self::assertLessThanOrEqual(0, $result->point()->get(0)->sub(0)->abs()->compare('0.000001'));
        self::assertLessThanOrEqual(0, $result->point()->get(1)->sub(0)->abs()->compare('0.000001'));
    }

    public function testNelderMeadMaximizeReturnsOriginalObjectiveValue(): void
    {
        $result = NelderMead::maximize(
            static fn (Vector $x): Number => Number::of(10)->sub($x->get(0)->sub(2)->pow(2)),
            [Vector::of(0), Vector::of(4)],
        );

        self::assertTrue($result->converged());
        self::assertSame(0, $result->value()->compare(10));
    }

    public function testNelderMeadRejectsSimplexWithIncorrectVertexCount(): void
    {
        foreach ([
            [Vector::of(0)],
            [Vector::of(0), Vector::of(1), Vector::of(2)],
        ] as $simplex) {
            try {
                NelderMead::minimize(static fn (Vector $point): Number => $point->get(0), $simplex);
                self::fail('Expected invalid simplex vertex count to be rejected.');
            } catch (InvalidArgumentException $exception) {
                self::assertStringContainsString('dimension + 1', $exception->getMessage());
            }
        }
    }

    public function testNelderMeadRejectsEmptySimplex(): void
    {
        $this->expectException(InvalidArgumentException::class);

        NelderMead::minimize(static fn (Vector $point): Number => $point->get(0), []);
    }

    public function testNelderMeadRejectsSimplexWithInconsistentDimensions(): void
    {
        $this->expectException(InvalidArgumentException::class);

        NelderMead::minimize(
            static fn (Vector $point): Number => $point->get(0),
            [Vector::of(0, 0), Vector::of(1, 1), Vector::of(2)],
        );
    }

    public function testNelderMeadRejectsDuplicateVertices(): void
    {
        $this->expectException(InvalidArgumentException::class);

        NelderMead::minimize(
            static fn (Vector $point): Number => $point->get(0),
            [Vector::of(0), Vector::of('0.0')],
        );
    }

    public function testNelderMeadClipsOutOfBoundsCandidatesBeforeObjectiveEvaluation(): void
    {
        $bounds = BoxConstraint::from(Vector::of('-0.75'), Vector::of('0.75'));
        $evaluatedPoints = [];

        $result = NelderMead::minimize(
            static function (Vector $point) use ($bounds, &$evaluatedPoints): Number {
                self::assertTrue($bounds->contains($point));
                $evaluatedPoints[] = $point;

                return $point->get(0)->pow(2);
            },
            [Vector::of('-0.5'), Vector::of('0.5')],
            '0.000001',
            10,
            $bounds,
        );

        self::assertNotEmpty($evaluatedPoints);
        self::assertTrue($result->converged() || $result->iterations() > 0);
    }

    public function testNelderMeadShrinksOriginalSimplexWhenOutsideContractionFails(): void
    {
        [$evaluatedPoints, $result] = $this->runNelderMeadStep([
            '0,0' => 0,
            '2,0' => 3,
            '0,2' => 10,
            '2,-2' => 5,
            '1.5,-1' => 6,
            '1,0' => 4,
            '0,1' => 4.5,
            '1,-1' => 9,
        ]);

        self::assertSame(
            ['0,0', '2,0', '0,2', '2,-2', '1.5,-1', '1,0', '0,1'],
            $evaluatedPoints,
        );
    }

    public function testNelderMeadAcceptsOutsideContractionAgainstReflectionValue(): void
    {
        [$evaluatedPoints, $result] = $this->runNelderMeadStep([
            '0,0' => 0,
            '2,0' => 3,
            '0,2' => 10,
            '2,-2' => 5,
            '1.5,-1' => -1,
        ]);

        self::assertSame(['0,0', '2,0', '0,2', '2,-2', '1.5,-1'], $evaluatedPoints);
        self::assertSame(0, $result->point()->get(0)->compare('1.5'));
        self::assertSame(0, $result->point()->get(1)->compare(-1));
    }

    public function testNelderMeadAcceptsInsideContractionAgainstWorstValue(): void
    {
        [$evaluatedPoints, $result] = $this->runNelderMeadStep([
            '0,0' => 0,
            '2,0' => 3,
            '0,2' => 10,
            '2,-2' => 12,
            '0.5,1' => -1,
        ]);

        self::assertSame(['0,0', '2,0', '0,2', '2,-2', '0.5,1'], $evaluatedPoints);
        self::assertSame(0, $result->point()->get(0)->compare('0.5'));
        self::assertSame(0, $result->point()->get(1)->compare(1));
    }

    public function testNelderMeadAcceptsReflectionWithoutContraction(): void
    {
        [$evaluatedPoints] = $this->runNelderMeadStep([
            '0,0' => 0,
            '2,0' => 3,
            '0,2' => 10,
            '2,-2' => 2,
        ]);

        self::assertSame(['0,0', '2,0', '0,2', '2,-2'], $evaluatedPoints);
    }

    public function testNelderMeadUsesExpansionOnlyWhenBetterThanReflection(): void
    {
        foreach ([
            [-2, -3, '3,-4'],
            [-2, -1, '2,-2'],
        ] as [$reflectionValue, $expansionValue, $expectedBestPoint]) {
            [, $result] = $this->runNelderMeadStep([
                '0,0' => 0,
                '2,0' => 3,
                '0,2' => 10,
                '2,-2' => $reflectionValue,
                '3,-4' => $expansionValue,
            ]);

            self::assertInstanceOf(Vector::class, $result->point());
            self::assertSame(0, $result->point()->get(0)->compare(explode(',', $expectedBestPoint)[0]));
            self::assertSame(0, $result->point()->get(1)->compare(explode(',', $expectedBestPoint)[1]));
        }
    }

    /**
     * @param array<string, int|float|string> $objectiveValues
     * @return array{list<string>, OptimizationResult}
     */
    private function runNelderMeadStep(array $objectiveValues): array
    {
        $evaluatedPoints = [];
        $result = NelderMead::minimize(
            static function (Vector $point) use (&$evaluatedPoints, $objectiveValues): Number {
                $key = (string) $point->get(0)->value() . ',' . (string) $point->get(1)->value();
                $evaluatedPoints[] = $key;

                if (! array_key_exists($key, $objectiveValues)) {
                    throw new LogicException("Unexpected Nelder-Mead candidate: {$key}");
                }

                return Number::of($objectiveValues[$key]);
            },
            [Vector::of(0, 0), Vector::of(2, 0), Vector::of(0, 2)],
            '0.000001',
            1,
        );

        return [$evaluatedPoints, $result];
    }

    public function testBoxConstraintRejectsInvalidBounds(): void
    {
        $this->expectException(InvalidArgumentException::class);

        BoxConstraint::from(
            Vector::of(5),
            Vector::of(1),
        );
    }

    public function testBoxConstraintContainsBothBoundariesButNotOutsideValues(): void
    {
        $constraint = BoxConstraint::from(Vector::of(-1), Vector::of(2));

        self::assertTrue($constraint->contains(Vector::of(-1)));
        self::assertTrue($constraint->contains(Vector::of(2)));
        self::assertFalse($constraint->contains(Vector::of('-1.0001')));
        self::assertFalse($constraint->contains(Vector::of('2.0001')));
    }
}
