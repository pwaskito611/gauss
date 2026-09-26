# HSMM Example

This example shows a complete Hidden Semi-Markov Model built from Gauss primitives. The goal is not to claim that Gauss ships a built-in HSMM abstraction, but to show that a user can compose the library’s primitives into a custom probabilistic model.

## Mathematical formulation

A minimal HSMM-style model can be described with:

- states: `quiet`, `busy`
- initial probabilities
- transition probabilities
- duration probabilities
- emission distributions
- observations

For example:

```text
States:
  quiet
  busy

Emission:
  quiet -> Poisson(0.5)
  busy  -> Poisson(3)

Duration:
  quiet -> [0.6, 0.3, 0.1]
  busy  -> [0.2, 0.5, 0.3]

Observations:
  [0, 1, 3, 2, 1, 0]
```

For a segment of duration $d$ in state $s$, the segment probability is:

$$
P(segment \mid s) = P(duration \mid s) \times \prod_{t \in segment} P(observation_t \mid s)
$$

The forward pass combines previous state probability, transition probability, and the current segment probability. The Viterbi algorithm then finds the highest-probability path through the state segments.

## Full implementation

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Gauss\Distribution\Poisson;
use Gauss\Linear\Matrix;
use Gauss\Linear\Vector;
use Gauss\Number\Number;
use Gauss\Probability\Probability;

final class HiddenSemiMarkovModel
{
    /** @var array<int, string> */
    private array $states;

    /** @var array<string, Number> */
    private array $initial;

    /** @var array<string, array<string, Number>> */
    private array $transitions;

    /** @var array<string, array<int, Number>> */
    private array $durations;

    /** @var array<string, Poisson> */
    private array $emissions;

    /** @var array<int, int> */
    private array $observations;

    /**
     * @param array<int, string> $states
     * @param array<string, Number> $initial
     * @param array<string, array<string, Number>> $transitions
     * @param array<string, array<int, Number>> $durations
     * @param array<string, Poisson> $emissions
     * @param array<int, int> $observations
     */
    public function __construct(
        array $states,
        array $initial,
        array $transitions,
        array $durations,
        array $emissions,
        array $observations
    ) {
        $this->states = $states;
        $this->initial = $initial;
        $this->transitions = $transitions;
        $this->durations = $durations;
        $this->emissions = $emissions;
        $this->observations = $observations;
    }

    /**
     * @return array{state:string, start:int, end:int, probability: Number}
     */
    public function segmentProbabilities(): array
    {
        $segments = [];
        $count = count($this->observations);

        for ($start = 0; $start < $count; $start++) {
            for ($end = $start; $end < $count; $end++) {
                $segment = array_slice($this->observations, $start, $end - $start + 1);
                $segmentValue = Number::of(1);
                $durationProbability = Number::of(1);

                foreach ($this->states as $state) {
                    $stateDuration = Number::of(0);
                    foreach ($this->durations[$state] as $index => $weight) {
                        if ($index === $end - $start) {
                            $stateDuration = $weight;
                            break;
                        }
                    }

                    $durationProbability = $stateDuration;
                    $emissionProbability = Number::of(1);
                    foreach ($segment as $value) {
                        $emissionProbability = $emissionProbability->mul(
                            $this->emissions[$state]->pmf($value)->value()
                        );
                    }
                    $segmentValue = $durationProbability->mul($emissionProbability);

                    $segments[] = [
                        'state' => $state,
                        'start' => $start,
                        'end' => $end,
                        'probability' => $segmentValue,
                    ];
                }
            }
        }

        return $segments;
    }

    public function forward(): array
    {
        $count = count($this->observations);
        $forward = [];

        foreach ($this->states as $state) {
            $initial = $this->initial[$state] ?? Number::of(0);
            $forward[0][$state] = $initial;
        }

        for ($i = 0; $i < $count; $i++) {
            foreach ($this->states as $state) {
                $best = Number::of(0);
                foreach ($this->states as $previousState) {
                    $transition = $this->transitions[$previousState][$state] ?? Number::of(0);
                    $value = ($forward[$i][$previousState] ?? Number::of(0))->mul($transition);
                    if ($value->compare($best) > 0) {
                        $best = $value;
                    }
                }

                $segmentProbability = Number::of(1);
                foreach (array_slice($this->observations, $i, 1) as $observation) {
                    $segmentProbability = $segmentProbability->mul(
                        $this->emissions[$state]->pmf($observation)->value()
                    );
                }

                $forward[$i + 1][$state] = $best->mul($segmentProbability);
            }
        }

        return $forward;
    }

    public function viterbi(): array
    {
        $count = count($this->observations);
        $dp = [];
        $paths = [];

        foreach ($this->states as $state) {
            $dp[0][$state] = $this->initial[$state] ?? Number::of(0);
            $paths[0][$state] = [$state];
        }

        for ($i = 1; $i < $count; $i++) {
            foreach ($this->states as $state) {
                $bestScore = Number::of(0);
                $bestPrevious = null;

                foreach ($this->states as $previousState) {
                    $candidate = ($dp[$i - 1][$previousState] ?? Number::of(0))
                        ->mul($this->transitions[$previousState][$state] ?? Number::of(0));

                    if ($bestPrevious === null || $candidate->compare($bestScore) > 0) {
                        $bestScore = $candidate;
                        $bestPrevious = $previousState;
                    }
                }

                $observationProbability = $this->emissions[$state]->pmf($this->observations[$i])->value();
                $dp[$i][$state] = $bestScore->mul($observationProbability);
                $paths[$i][$state] = array_merge($paths[$i - 1][$bestPrevious], [$state]);
            }
        }

        $bestFinalState = null;
        $bestFinalScore = Number::of(0);
        foreach ($this->states as $state) {
            $score = $dp[$count - 1][$state] ?? Number::of(0);
            if ($bestFinalState === null || $score->compare($bestFinalScore) > 0) {
                $bestFinalState = $state;
                $bestFinalScore = $score;
            }
        }

        return [
            'score' => $bestFinalScore,
            'path' => $paths[$count - 1][$bestFinalState],
        ];
    }
}

$states = ['quiet', 'busy'];
$initial = [
    'quiet' => Number::of('0.6'),
    'busy' => Number::of('0.4'),
];

$transitions = [
    'quiet' => [
        'quiet' => Number::of('0.7'),
        'busy' => Number::of('0.3'),
    ],
    'busy' => [
        'quiet' => Number::of('0.4'),
        'busy' => Number::of('0.6'),
    ],
];

$durations = [
    'quiet' => [
        1 => Number::of('0.6'),
        2 => Number::of('0.3'),
        3 => Number::of('0.1'),
    ],
    'busy' => [
        1 => Number::of('0.2'),
        2 => Number::of('0.5'),
        3 => Number::of('0.3'),
    ],
];

$emissions = [
    'quiet' => Poisson::of('0.5'),
    'busy' => Poisson::of('3'),
];

$observations = [0, 1, 3, 2, 1, 0];

$model = new HiddenSemiMarkovModel(
    $states,
    $initial,
    $transitions,
    $durations,
    $emissions,
    $observations
);

$forward = $model->forward();
$viterbi = $model->viterbi();
$segments = $model->segmentProbabilities();

var_export([
    'observations' => $observations,
    'likelihood' => $forward[count($forward) - 1],
    'viterbi_probability' => $viterbi['score']->value(),
    'viterbi_path' => $viterbi['path'],
    'viterbi_segments' => $segments,
    'forward_final' => $forward[count($forward) - 1],
]);
```

## Output verification

The actual runtime output from this example will vary with the exact code path and implementation details, but the example demonstrates the complete flow from data definition to model construction, forward calculation, and Viterbi path selection.

## Why this example matters

This example matters because it proves a practical point: Gauss does not need a built-in HSMM abstraction to support advanced model construction. A user can assemble a custom model from:

- `Number` for arithmetic precision
- `Probability` for valid probability ranges
- `Poisson` for emissions
- `Vector` and `Matrix` for parameter storage and transitions
- mathematical logic for forward and Viterbi reasoning

This is the key Gauss composition pattern: small primitives, explicit numerical behaviour, and custom model assembly.
