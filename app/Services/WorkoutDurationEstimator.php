<?php

namespace App\Services;

use App\Models\Exercise;
use App\Support\TrainingSettings;

/**
 * Прогноз длительности: подходы × (время подхода + отдых) + разминка.
 */
class WorkoutDurationEstimator
{
    public function __construct(private readonly TrainingSettings $settings) {}

    public function exerciseMinutes(Exercise $exercise, int $sets): float
    {
        $setSeconds = config('training.set_duration_seconds.'.$exercise->mechanic, 40);
        $rest = $this->settings->restSeconds($exercise->isCompound());

        return round($sets * ($setSeconds + $rest) / 60, 1);
    }

    /** Общая разминка + мобильность + разминочные подходы (если есть базовое упражнение). */
    public function warmupMinutes(bool $hasCompound): float
    {
        $warmup = config('training.warmup');
        $minutes = $warmup['general_minutes'] + $warmup['mobility_items'];
        if ($hasCompound) {
            $minutes += count($warmup['ramp']) * $warmup['minutes_per_ramp_set'];
        }

        return (float) $minutes;
    }

    /**
     * @param  iterable<array{0: Exercise, 1: int}>  $items  пары [упражнение, подходов]
     */
    public function totalMinutes(iterable $items): float
    {
        $total = 0.0;
        $hasCompound = false;
        foreach ($items as [$exercise, $sets]) {
            $total += $this->exerciseMinutes($exercise, $sets);
            $hasCompound = $hasCompound || $exercise->isCompound();
        }

        return round($total + $this->warmupMinutes($hasCompound), 1);
    }
}
