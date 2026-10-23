<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutSet;
use App\Support\OneRepMax;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Аналитика по завершённым тренировкам: динамика упражнения, рекорды, недельный объём.
 */
class TrainingAnalytics
{
    /**
     * Сессии упражнения от старых к новым (для графиков).
     *
     * @return Collection<int, array{date: string, top_weight: float, volume: float, e1rm: float, sets: string}>
     */
    public function exerciseSessions(TrainingHistory $history, Exercise $exercise): Collection
    {
        return $history->sessionsFor($exercise->id)->reverse()->values()->map(function ($row) {
            $sets = $row['entry']->sets->where('is_warmup', false);

            return [
                'date' => $row['workout']->scheduled_on->toDateString(),
                'top_weight' => (float) $sets->max('weight_kg'),
                'volume' => round($sets->sum(fn (WorkoutSet $s) => $s->volume()), 1),
                'e1rm' => (float) $sets->map(fn (WorkoutSet $s) => $s->estimatedOneRepMax())->max(),
                'sets' => $sets->map(fn (WorkoutSet $s) => format_kg($s->weight_kg).'×'.$s->reps)->implode(', '),
            ];
        });
    }

    /**
     * Личные рекорды по каждому упражнению.
     *
     * @return Collection<int, array{exercise: Exercise, max_weight: float, max_weight_reps: int, max_weight_date: Carbon, best_e1rm: float, best_e1rm_date: Carbon, best_volume: float, sessions: int}>
     */
    public function records(TrainingHistory $history): Collection
    {
        $records = [];
        foreach ($history->workouts() as $workout) {
            foreach ($workout->workoutExercises as $we) {
                $sets = $we->sets->where('is_warmup', false);
                if ($sets->isEmpty()) {
                    continue;
                }
                $r = $records[$we->exercise_id] ?? [
                    'exercise' => $we->exercise, 'max_weight' => -1.0, 'max_weight_reps' => 0, 'max_weight_date' => null,
                    'best_e1rm' => -1.0, 'best_e1rm_date' => null, 'best_volume' => 0.0, 'sessions' => 0,
                ];
                $r['sessions']++;
                foreach ($sets as $s) {
                    // История идёт от новых к старым: при равенстве оставляем более раннюю дату.
                    if ($s->weight_kg > $r['max_weight'] || ($s->weight_kg == $r['max_weight'] && $s->reps >= $r['max_weight_reps'])) {
                        [$r['max_weight'], $r['max_weight_reps'], $r['max_weight_date']] = [$s->weight_kg, $s->reps, $workout->scheduled_on];
                    }
                    if ($s->estimatedOneRepMax() >= $r['best_e1rm']) {
                        [$r['best_e1rm'], $r['best_e1rm_date']] = [$s->estimatedOneRepMax(), $workout->scheduled_on];
                    }
                }
                $r['best_volume'] = max($r['best_volume'], $sets->sum(fn (WorkoutSet $s) => $s->volume()));
                $records[$we->exercise_id] = $r;
            }
        }

        return collect($records)->sortBy(fn ($r) => $r['exercise']->name)->values();
    }

    /**
     * Рабочие подходы на мышечную группу по неделям (последние $weeks недель, включая текущую).
     *
     * @return array{weeks: list<array{start: Carbon, label: string}>, sets: array<string, list<float>>, undertrained: list<string>, overtrained: list<string>}
     */
    public function weeklyMuscleVolume(User $user, TrainingHistory $history, int $weeks = 6, ?Carbon $now = null): array
    {
        $settings = $user->trainingSettings();
        $now ??= Carbon::today();
        $half = $settings->bool('count_secondary_as_half');

        $weekList = [];
        $sets = array_fill_keys(array_keys(config('training.muscles')), []);
        for ($i = $weeks - 1; $i >= 0; $i--) {
            $start = $now->copy()->startOfWeek()->subWeeks($i);
            $weekList[] = ['start' => $start, 'label' => $start->format('d.m')];
            $week = $history->setsPerMuscle($start, $start->copy()->endOfWeek(), $half);
            foreach ($sets as $muscle => $_) {
                $sets[$muscle][] = round($week[$muscle] ?? 0, 1);
            }
        }

        // Оцениваем последнюю полную неделю, если текущая только началась.
        $index = $now->isSunday() ? $weeks - 1 : $weeks - 2;
        $index = max(0, $index);
        $min = $settings->int('weekly_sets_min');
        $max = $settings->int('weekly_sets_max');

        return [
            'weeks' => $weekList,
            'sets' => $sets,
            'evaluated_week' => $index,
            'undertrained' => array_keys(array_filter($sets, fn ($v) => $v[$index] < $min)),
            'overtrained' => array_keys(array_filter($sets, fn ($v) => $v[$index] > $max)),
        ];
    }

    /**
     * Итоги тренировки: длительность, объём, подходы и новые рекорды относительно прошлых тренировок.
     */
    public function summary(Workout $workout): array
    {
        $workout->loadMissing('workoutExercises.exercise', 'workoutExercises.sets');
        $history = new TrainingHistory($workout->user, $workout->scheduled_on, $workout->id);
        $previous = $this->records($history)->keyBy(fn ($r) => $r['exercise']->id);

        $volume = 0.0;
        $setCount = 0;
        $newRecords = [];
        foreach ($workout->workoutExercises as $we) {
            $sets = $we->sets->where('is_warmup', false);
            if ($sets->isEmpty()) {
                continue;
            }
            $volume += $sets->sum(fn (WorkoutSet $s) => $s->volume());
            $setCount += $sets->count();

            $maxWeight = (float) $sets->max('weight_kg');
            $bestE1rm = (float) $sets->map(fn (WorkoutSet $s) => $s->estimatedOneRepMax())->max();
            $prev = $previous->get($we->exercise_id);

            if (! $prev) {
                continue; // первое выполнение — не рекорд, а точка отсчёта
            }
            if ($maxWeight > $prev['max_weight']) {
                $newRecords[] = ['exercise' => $we->exercise, 'type' => 'Рабочий вес', 'value' => $maxWeight, 'previous' => $prev['max_weight']];
            }
            if ($bestE1rm > $prev['best_e1rm']) {
                $newRecords[] = ['exercise' => $we->exercise, 'type' => 'Расчётный 1ПМ', 'value' => $bestE1rm, 'previous' => $prev['best_e1rm']];
            }
        }

        return [
            'duration' => $workout->durationMinutes(),
            'volume' => round($volume),
            'sets' => $setCount,
            'exercises' => $workout->workoutExercises->filter(fn ($we) => $we->sets->isNotEmpty())->count(),
            'records' => $newRecords,
        ];
    }
}
