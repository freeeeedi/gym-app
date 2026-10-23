<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\WarmupExercise;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use Illuminate\Support\Collection;

/**
 * Разминка под выбранные упражнения:
 *  - общая: кардио, лучше всего совпадающее с нагружаемыми мышцами;
 *  - специфическая: мобильность/активация, жадно покрывающая основные мышцы;
 *  - разминочные подходы первого базового упражнения в % от рабочего веса.
 */
class WarmupPlanner
{
    public function __construct(private readonly ProgressionAdvisor $advisor) {}

    public function plan(Workout $workout, ?TrainingHistory $history = null): ?WarmupPlan
    {
        $workout->loadMissing(['user', 'workoutExercises.exercise.muscles']);
        $items = $workout->workoutExercises;
        if ($items->isEmpty()) {
            return null;
        }

        $focus = $this->focusMuscles($items);
        $library = WarmupExercise::query()->with('muscles')->orderBy('id')->get();

        $general = $library->where('type', 'general')
            ->sortByDesc(fn (WarmupExercise $w) => count(array_intersect($w->muscleSlugs(), array_slice($focus, 0, 3))))
            ->first();

        $mobility = $this->pickMobility($library->where('type', 'mobility'), $focus, config('training.warmup.mobility_items'));

        [$rampExercise, $workingWeight, $rampSets] = $this->ramp($workout, $items, $history);

        return new WarmupPlan($focus, $general, $mobility, $rampExercise, $workingWeight, $rampSets);
    }

    /**
     * Мышцы по суммарной нагрузке: основная — подход целиком, вспомогательная — половина.
     *
     * @return list<string>
     */
    private function focusMuscles(Collection $items): array
    {
        $load = [];
        foreach ($items as $we) {
            TrainingHistory::addSets($load, $we->exercise->primaryMuscles(), $we->exercise->secondaryMuscles(), $we->planned_sets, true);
        }
        arsort($load);

        // Основная нагрузка — группы, получающие хотя бы половину от максимума.
        $max = reset($load);

        return array_keys(array_filter($load, fn ($v) => $v >= $max / 2));
    }

    /** Жадное покрытие: на каждом шаге упражнение, закрывающее больше всего ещё не размятых мышц. */
    private function pickMobility(Collection $pool, array $focus, int $limit): array
    {
        $uncovered = $focus;
        $picked = [];
        while (count($picked) < $limit && $pool->isNotEmpty()) {
            $best = $pool->sortByDesc(fn (WarmupExercise $w) => [
                count(array_intersect($w->muscleSlugs(), $uncovered)),
                count(array_intersect($w->muscleSlugs(), $focus)),
            ])->first();
            if (! array_intersect($best->muscleSlugs(), $focus)) {
                break;
            }
            $picked[] = $best;
            $uncovered = array_values(array_diff($uncovered, $best->muscleSlugs()));
            $pool = $pool->reject(fn ($w) => $w->id === $best->id);
        }

        return $picked;
    }

    /** @return array{0: ?Exercise, 1: ?float, 2: list<array{percent: int, reps: int, weight: ?float}>} */
    private function ramp(Workout $workout, Collection $items, ?TrainingHistory $history): array
    {
        /** @var WorkoutExercise|null $first */
        $first = $items->first(fn (WorkoutExercise $we) => $we->exercise->isCompound());
        if (! $first) {
            return [null, null, []];
        }
        $exercise = $first->exercise;
        $history ??= new TrainingHistory($workout->user, $workout->scheduled_on, $workout->id);
        $advice = $this->advisor->advise($exercise, $history);

        $assisted = in_array($exercise->slug, config('training.assisted_exercises'), true);
        $working = $assisted ? null : $advice?->nextWeight();
        if ($working !== null && $working <= 0) {
            $working = null;
        }

        $sets = [];
        foreach (config('training.warmup.ramp') as $step) {
            $sets[] = [
                'percent' => $step['percent'],
                'reps' => $step['reps'],
                'weight' => $working ? $this->rampWeight($exercise, $working * $step['percent'] / 100) : null,
            ];
        }

        return [$exercise, $working, $sets];
    }

    private function rampWeight(Exercise $exercise, float $weight): float
    {
        $step = $exercise->equipment === 'dumbbell' ? 1.0 : 2.5;
        $rounded = round($weight / $step) * $step;
        if ($exercise->equipment === 'barbell') {
            $rounded = max((float) config('training.warmup.barbell_min_kg'), $rounded);
        }

        return $rounded;
    }
}
