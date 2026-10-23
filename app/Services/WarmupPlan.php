<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\WarmupExercise;

final class WarmupPlan
{
    /**
     * @param  list<string>  $focusMuscles  мышцы с основной нагрузкой, по убыванию
     * @param  list<WarmupExercise>  $mobility
     * @param  list<array{percent: int, reps: int, weight: ?float}>  $rampSets
     */
    public function __construct(
        public readonly array $focusMuscles,
        public readonly ?WarmupExercise $general,
        public readonly array $mobility,
        public readonly ?Exercise $rampExercise,
        public readonly ?float $workingWeight,
        public readonly array $rampSets,
    ) {}

    public function minutes(): int
    {
        $minutes = ($this->general?->minutes ?? 0) + collect($this->mobility)->sum('minutes');

        return (int) ceil($minutes + count($this->rampSets) * config('training.warmup.minutes_per_ramp_set'));
    }
}
