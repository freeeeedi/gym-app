<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\WorkoutSet;
use App\Support\OneRepMax;

/**
 * Двойная прогрессия: держим вес и добавляем повторения до верхней границы диапазона
 * во всех подходах, затем повышаем вес на шаг. Плюс признак стагнации по расчётному 1ПМ.
 */
class ProgressionAdvisor
{
    public function advise(Exercise $exercise, TrainingHistory $history, int $stagnationSessions = 3): ?ProgressionAdvice
    {
        $sessions = $history->sessionsFor($exercise->id);
        if ($sessions->isEmpty()) {
            return null;
        }

        $last = $sessions->first();
        $sets = $last['entry']->sets->where('is_warmup', false)->values();
        $top = (float) $sets->max('weight_kg');
        $topSets = $sets->filter(fn (WorkoutSet $s) => (float) $s->weight_kg === $top);
        $assisted = in_array($exercise->slug, config('training.assisted_exercises'), true);
        $step = (float) config('training.weight_increments.'.$exercise->equipment, 2.5);
        $lastSets = $sets->map(fn (WorkoutSet $s) => ['weight' => (float) $s->weight_kg, 'reps' => $s->reps])->all();

        if ($topSets->every(fn (WorkoutSet $s) => $s->reps >= $exercise->rep_max)) {
            if ($assisted) {
                $next = max(0, $top - $step);
                $message = sprintf('Все подходы на верхней границе (%d повт.) — уменьшите противовес до %s кг.', $exercise->rep_max, format_kg($next));
            } elseif ($exercise->equipment === 'bodyweight' && $top == 0) {
                $next = $step;
                $message = sprintf('Все подходы на %d+ повторений — добавьте отягощение %s кг.', $exercise->rep_max, format_kg($step));
            } else {
                $next = $top + $step;
                $message = sprintf('Все подходы на верхней границе (%d повт.) — повысьте вес до %s кг.', $exercise->rep_max, format_kg($next));
            }
            $action = 'increase';
        } elseif ($topSets->contains(fn (WorkoutSet $s) => $s->reps < $exercise->rep_min)) {
            $next = $top;
            $action = 'hold';
            $message = sprintf('Есть подходы ниже %d повт. — оставьте %s кг и добейтесь диапазона %d–%d.', $exercise->rep_min, format_kg($top), $exercise->rep_min, $exercise->rep_max);
        } else {
            $next = $top;
            $action = 'reps';
            $message = sprintf('Оставьте %s кг и добавляйте повторения: цель — %d во всех подходах.', format_kg($top), $exercise->rep_max);
        }

        [$stagnating, $stagnationMessage] = $assisted ? [false, null] : $this->stagnation($sessions, $stagnationSessions);

        return new ProgressionAdvice($last['workout']->scheduled_on, $lastSets, $action, $next, $message, $stagnating, $stagnationMessage);
    }

    /**
     * Стагнация: в последних N тренировках лучший расчётный 1ПМ не превысил прежний рекорд.
     *
     * @return array{0: bool, 1: ?string}
     */
    private function stagnation($sessions, int $n): array
    {
        if ($sessions->count() < $n + 1) {
            return [false, null];
        }
        $best = $sessions->map(fn ($row) => $row['entry']->sets->where('is_warmup', false)
            ->map(fn (WorkoutSet $s) => OneRepMax::epley((float) $s->weight_kg, $s->reps))->max());

        $recent = $best->take($n)->max();
        $before = $best->slice($n)->max();
        if ($recent > $before) {
            return [false, null];
        }

        return [true, sprintf(
            'Нет роста %d тренировки подряд (расчётный 1ПМ %s кг против рекорда %s кг). Рекомендация: разгрузочная неделя — вес −10–15 %%, подходов вдвое меньше; затем вернитесь к работе или смените вариант упражнения.',
            $n, format_kg($recent), format_kg($before),
        )];
    }
}
