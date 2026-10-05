# HSMM Example

This page documents a custom explicit-duration Hidden Semi-Markov Model built from Gauss primitives. The intent is to show how to assemble the right model components from reusable math primitives without claiming that Gauss ships a built-in `HiddenSemiMarkovModel` abstraction.

## Mathematical formulation

A hidden semi-Markov model explicitly models the duration of each state segment. The core ingredients are:

- initial state probability: $\pi_s$
- transition probability: $A_{i,j}$
- emission probability: $b_s(O_t)$
- duration probability: $p(d \mid s)$
- segment boundaries: $(s, start, end)$

For a segment spanning `start` to `end`, the duration is inclusive:

$$
d = end - start + 1
$$

A single segment contribution is:

$$
E(s, start, end) = p(d \mid s) \times \prod_{t=start}^{end} b_s(O_t)
$$

The forward pass marginalizes over all valid segmentations, and the Viterbi pass maximizes over all valid segmentations while tracking backpointers for state and duration.

## Example setup

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

The segment length for `start = 1` and `end = 3` is `3`, not `2`.

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
     * Local segment contribution for a single state-duration segment:
     *
     *     P(d | s) * \prod_{t=start}^{end} b_s(O_t)
     *
     * This is not the full segmentation probability because it excludes the
     * initial state term and any transition terms between segments.
     */
    public function segmentProbabilities(): array
    {
        $segments = [];
        $count = count($this->observations);

        for ($start = 0; $start < $count; $start++) {
            for ($end = $start; $end < $count; $end++) {
                $duration = $end - $start + 1;

                foreach ($this->states as $state) {
                    $segments[] = [
                        'state' => $state,
                        'start' => $start,
                        'end' => $end,
                        'duration' => $duration,
                        'probability' => $this->segmentContribution($state, $start, $end),
                    ];
                }
            }
        }

        return $segments;
    }

    /**
     * Forward probability with explicit duration modeling.
     *
     * alpha[t][j] = sum over valid segment lengths d and predecessor states of:
     *
     *     E(j, t-d+1, t) * (pi_j if t-d+1 == 0 else sum_i alpha[t-d][i] * A[i,j])
     */
    public function forward(): array
    {
        $count = count($this->observations);
        $forward = array_fill(0, $count, []);

        for ($t = 0; $t < $count; $t++) {
            foreach ($this->states as $state) {
                $total = Number::of(0);

                for ($duration = 1; $duration <= $t + 1; $duration++) {
                    $start = $t - $duration + 1;
                    $segmentScore = $this->segmentContribution($state, $start, $t);

                    if ($segmentScore->compare(0) === 0) {
                        continue;
                    }

                    if ($start === 0) {
                        $candidate = ($this->initial[$state] ?? Number::of(0))->mul($segmentScore);
                    } else {
                        $previousValue = Number::of(0);
                        foreach ($this->states as $previousState) {
                            $previousValue = $previousValue->add(
                                ($forward[$start - 1][$previousState] ?? Number::of(0))
                                    ->mul($this->transitions[$previousState][$state] ?? Number::of(0))
                            );
                        }

                        $candidate = $previousValue->mul($segmentScore);
                    }

                    $total = $total->add($candidate);
                }

                $forward[$t][$state] = $total;
            }
        }

        return $forward;
    }

    /**
     * Viterbi with explicit duration and backpointer tracking.
     *
     * @return array{score:Number, segments:array<int, array{state:string, start:int, end:int, duration:int}>}
     */
    public function viterbi(): array
    {
        $count = count($this->observations);
        $delta = array_fill(0, $count, []);
        $backpointer = array_fill(0, $count, []);

        for ($t = 0; $t < $count; $t++) {
            foreach ($this->states as $state) {
                $bestScore = null;
                $bestPreviousState = null;
                $bestPreviousTime = null;
                $bestDuration = null;

                for ($duration = 1; $duration <= $t + 1; $duration++) {
                    $start = $t - $duration + 1;
                    $segmentScore = $this->segmentContribution($state, $start, $t);

                    if ($segmentScore->compare(0) === 0) {
                        continue;
                    }

                    if ($start === 0) {
                        $candidate = ($this->initial[$state] ?? Number::of(0))->mul($segmentScore);
                        $bestPreviousStateCandidate = null;
                    } else {
                        $bestPrevious = null;
                        $bestPreviousStateCandidate = null;

                        foreach ($this->states as $previousState) {
                            $score = ($delta[$start - 1][$previousState] ?? Number::of(0))
                                ->mul($this->transitions[$previousState][$state] ?? Number::of(0));

                            if ($bestPrevious === null || $score->compare($bestPrevious) > 0) {
                                $bestPrevious = $score;
                                $bestPreviousStateCandidate = $previousState;
                            }
                        }

                        if ($bestPrevious === null) {
                            continue;
                        }

                        $candidate = $bestPrevious->mul($segmentScore);
                    }

                    if ($bestScore === null || $candidate->compare($bestScore) > 0) {
                        $bestScore = $candidate;
                        $bestPreviousState = $bestPreviousStateCandidate;
                        $bestPreviousTime = $start === 0 ? null : $start - 1;
                        $bestDuration = $duration;
                    }
                }

                if ($bestScore !== null) {
                    $delta[$t][$state] = $bestScore;
                    $backpointer[$t][$state] = [
                        'previousState' => $bestPreviousState,
                        'previousTime' => $bestPreviousTime,
                        'duration' => $bestDuration,
                    ];
                }
            }
        }

        $bestFinalState = null;
        $bestFinalScore = null;
        foreach ($this->states as $state) {
            $score = $delta[$count - 1][$state] ?? Number::of(0);
            if ($bestFinalScore === null || $score->compare($bestFinalScore) > 0) {
                $bestFinalState = $state;
                $bestFinalScore = $score;
            }
        }

        $segments = [];
        $state = $bestFinalState;
        $timeIndex = $count - 1;

        while ($state !== null && $timeIndex >= 0) {
            $info = $backpointer[$timeIndex][$state] ?? null;
            if ($info === null) {
                break;
            }

            $segments[] = [
                'state' => $state,
                'start' => $timeIndex - $info['duration'] + 1,
                'end' => $timeIndex,
                'duration' => $info['duration'],
            ];

            if ($info['previousState'] === null) {
                break;
            }

            $state = $info['previousState'];
            $timeIndex = $info['previousTime'];
        }

        $segments = array_reverse($segments);

        return [
            'score' => $bestFinalScore ?? Number::of(0),
            'segments' => $segments,
        ];
    }

    private function segmentContribution(string $state, int $start, int $end): Number
    {
        $duration = $end - $start + 1;
        $durationProbability = $this->durationProbability($state, $duration);
        $emissionProbability = Number::of(1);

        for ($index = $start; $index <= $end; $index++) {
            $emissionProbability = $emissionProbability->mul(
                $this->emissions[$state]->pmf($this->observations[$index])->value()
            );
        }

        return $durationProbability->mul($emissionProbability);
    }

    private function durationProbability(string $state, int $duration): Number
    {
        if ($duration < 1) {
            return Number::of(0);
        }

        return $this->durations[$state][$duration] ?? Number::of(0);
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
    'segment_scores' => $segments,
    'forward_final_state_scores' => $forward[count($forward) - 1],
    'viterbi_score' => $viterbi['score']->value(),
    'viterbi_segments' => $viterbi['segments'],
]);
```

## Output verification

This example exercises the core HSMM mechanics explicitly: segment contributions, initial state handling, transition-weighted continuation, and duration-aware Viterbi backtracking. The exact numeric values depend on the model parameters, but the structure of the result is the important part.

## Why this example matters

This example matters because it proves a practical point: Gauss does not need a built-in HSMM abstraction to support advanced model construction. A user can assemble a custom explicit-duration model from:

- `Number` for precise arithmetic
- `Probability` for constrained values
- `Poisson` for emission probabilities
- `Vector` and `Matrix` for parameter storage and transitions
- explicit HSMM logic for segment evaluation, forward aggregation, and Viterbi backtracking

This is the key Gauss composition pattern: small primitives, explicit numerical behavior, and a mathematically meaningful model built from them.
