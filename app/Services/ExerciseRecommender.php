<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\Muscle;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Services\Recommendation\Recommendation;
use App\Services\Recommendation\Suggestion;
use App\Services\Recommendation\SuggestionGroup;
use App\Support\TrainingSettings;
use Illuminate\Support\Collection;

/**
 * Подбор упражнений для тренировки по прозрачным правилам (config/training.php):
 *  1. покрытие паттернов и мышц дня;
 *  2. пустая тренировка — все паттерны дня;
 *  3. ротация — без упражнений из последних N тренировок на мышцу;
 *  4. бюджет времени;
 *  5. базовые выше изолирующих;
 *  6. лимиты объёма на тренировку и неделю.
 * Каждая подсказка содержит список причин.
 */
class ExerciseRecommender
{
    public function recommend(Workout $workout, ?TrainingHistory $history = null): Recommendation
    {
        $workout->loadMissing(['user', 'template.muscles', 'workoutExercises.exercise.muscles']);
        $user = $workout->user;
        $settings = $user->trainingSettings();
        $scoring = config('training.scoring');
        $estimator = new WorkoutDurationEstimator($settings);
        $history ??= new TrainingHistory($user, $workout->scheduled_on, $workout->id);

        /** @var Collection<int, WorkoutExercise> $selected */
        $selected = $workout->workoutExercises;
        $selectedItems = $selected->map(fn (WorkoutExercise $we) => [$we->exercise, $we->planned_sets])->all();

        $requiredPatterns = $workout->template?->patterns ?? [];
        $dayMuscles = $this->dayMuscles($workout, $selected);
        $coveredPatterns = $selected->pluck('exercise.pattern')->unique()->values()->all();
        $missingPatterns = array_values(array_diff($requiredPatterns, $coveredPatterns));

        // Сколько упражнений и подходов уже стоит на каждую мышцу в этой тренировке.
        $sessionExercises = [];
        $plannedSets = [];
        $halfSecondary = $settings->bool('count_secondary_as_half');
        foreach ($selected as $we) {
            foreach ($we->exercise->primaryMuscles() as $m) {
                $sessionExercises[$m] = ($sessionExercises[$m] ?? 0) + 1;
            }
            TrainingHistory::addSets($plannedSets, $we->exercise->primaryMuscles(), $we->exercise->secondaryMuscles(), $we->planned_sets, $halfSecondary);
        }

        // Недельный объём: завершённые тренировки этой недели + план текущей.
        $weekStart = $workout->scheduled_on->copy()->startOfWeek();
        $weekEnd = $workout->scheduled_on->copy()->endOfWeek();
        $weeklySets = $history->setsPerMuscle($weekStart, $weekEnd, $halfSecondary);
        foreach ($plannedSets as $m => $n) {
            $weeklySets[$m] = ($weeklySets[$m] ?? 0) + $n;
        }

        $currentMinutes = $estimator->totalMinutes($selectedItems);
        $budget = $settings->int('time_budget_minutes');
        $window = $settings->int('rotation_window');
        $lastUsed = $history->lastUsedDates();
        $today = $workout->scheduled_on;

        $recentByMuscle = [];
        foreach ($dayMuscles as $m) {
            $recentByMuscle[$m] = $history->recentExercisesForMuscle($m, $window);
        }

        $candidates = Exercise::query()
            ->visibleTo($user)
            ->whereNotIn('id', $selected->pluck('exercise_id'))
            ->with('muscles')
            ->orderBy('name')
            ->get();

        $allowed = [];
        $rotationBlocked = [];
        $excluded = [];

        foreach ($candidates as $exercise) {
            $targetMuscles = array_values(array_intersect($exercise->primaryMuscles(), $dayMuscles));
            if (! $targetMuscles) {
                continue; // не относится к мышцам дня
            }
            if ($exercise->equipment === 'bodyweight' && ! $settings->bool('include_bodyweight')) {
                $excluded[] = ['exercise' => $exercise, 'reason' => 'Упражнения с собственным весом отключены в настройках.'];

                continue;
            }

            $sets = $settings->defaultSets($exercise->isCompound());
            $minutes = $estimator->exerciseMinutes($exercise, $sets);
            $projected = $estimator->totalMinutes([...$selectedItems, [$exercise, $sets]]);

            // Правило 6: лимит упражнений на мышцу за тренировку.
            $perMuscleMax = $settings->int('exercises_per_muscle_max');
            $saturated = array_filter($targetMuscles, fn ($m) => ($sessionExercises[$m] ?? 0) >= $perMuscleMax);
            if (count($saturated) === count($targetMuscles)) {
                $excluded[] = ['exercise' => $exercise, 'reason' => sprintf('На «%s» уже %d упр. — достаточно для одной тренировки.', $this->muscleNames($saturated), $perMuscleMax)];

                continue;
            }

            // Правило 6: недельный максимум подходов.
            $weeklyMax = $settings->int('weekly_sets_max');
            $overWeekly = array_filter($targetMuscles, fn ($m) => ($weeklySets[$m] ?? 0) + $sets > $weeklyMax);
            if (count($overWeekly) === count($targetMuscles)) {
                $excluded[] = ['exercise' => $exercise, 'reason' => sprintf('Недельный объём на «%s» превысит %d подходов.', $this->muscleNames($overWeekly), $weeklyMax)];

                continue;
            }

            // Правило 4: бюджет времени.
            if ($projected > $budget) {
                $excluded[] = ['exercise' => $exercise, 'reason' => sprintf('Не помещается во время: прогноз %s мин при бюджете %d мин.', $this->num($projected), $budget)];

                continue;
            }

            [$score, $reasons] = $this->score(
                $exercise, $targetMuscles, $requiredPatterns, $coveredPatterns, $sessionExercises,
                $weeklySets, $lastUsed[$exercise->id] ?? null, $today, $budget - $projected, $settings, $scoring,
            );
            $suggestion = new Suggestion($exercise, $sets, $minutes, $projected, $score, $reasons);

            // Правило 3: ротация.
            $recentHit = null;
            foreach ($exercise->primaryMuscles() as $m) {
                if (isset($recentByMuscle[$m][$exercise->id])) {
                    $recentHit = [$m, $recentByMuscle[$m][$exercise->id]];
                    break;
                }
            }
            if ($recentHit) {
                $reason = sprintf('Ротация: выполнялось %s — это одна из последних %d тренировок на «%s».', $recentHit[1]->format('d.m'), $window, Muscle::label($recentHit[0]));
                $rotationBlocked[] = ['suggestion' => $suggestion, 'date' => $lastUsed[$exercise->id] ?? $recentHit[1]];
                $excluded[] = ['exercise' => $exercise, 'reason' => $reason];

                continue;
            }

            $allowed[] = $suggestion;
        }

        if ($settings->bool('rotation_fallback')) {
            $allowed = $this->rotationFallback($allowed, $rotationBlocked, $missingPatterns, $excluded);
        }

        return new Recommendation(
            groups: $this->group($allowed, $requiredPatterns, $coveredPatterns),
            excluded: $excluded,
            currentMinutes: $currentMinutes,
            budgetMinutes: $budget,
            dayMuscles: $dayMuscles,
            coveredPatterns: $coveredPatterns,
            missingPatterns: $missingPatterns,
            sessionExercises: $sessionExercises,
            weeklySets: $weeklySets,
        );
    }

    /**
     * Мышцы дня: из шаблона; без шаблона — основные мышцы уже выбранных упражнений;
     * если и их нет — все группы.
     *
     * @return list<string>
     */
    private function dayMuscles(Workout $workout, Collection $selected): array
    {
        if ($workout->template) {
            return $workout->template->muscleSlugs();
        }
        $fromSelection = $selected->flatMap(fn (WorkoutExercise $we) => $we->exercise->primaryMuscles())->unique()->values()->all();

        return $fromSelection ?: array_keys(config('training.muscles'));
    }

    /**
     * @return array{0: int, 1: list<string>}
     */
    private function score(
        Exercise $exercise,
        array $targetMuscles,
        array $requiredPatterns,
        array $coveredPatterns,
        array $sessionExercises,
        array $weeklySets,
        $lastUsed,
        $today,
        float $minutesLeftAfter,
        TrainingSettings $settings,
        array $scoring,
    ): array {
        $score = 0;
        $reasons = [];
        $pattern = $exercise->patternLabel();

        // Правило 1–2: покрытие паттернов.
        if (in_array($exercise->pattern, $requiredPatterns, true) && ! in_array($exercise->pattern, $coveredPatterns, true)) {
            $score += $scoring['missing_required_pattern'];
            $covered = array_values(array_intersect($coveredPatterns, $requiredPatterns));
            $reasons[] = $covered
                ? sprintf('Закрывает паттерн «%s»: уже есть %s, а этого движения в тренировке ещё нет.', $pattern, $this->patternNames($covered))
                : sprintf('Обязательный паттерн дня «%s».', $pattern);
        } elseif (! in_array($exercise->pattern, $coveredPatterns, true)) {
            $score += $scoring['new_pattern'];
            $reasons[] = sprintf('Добавляет новый паттерн «%s».', $pattern);
        }

        // Правило 1: покрытие мышц.
        $min = $settings->int('exercises_per_muscle_min');
        $untouched = array_filter($targetMuscles, fn ($m) => ($sessionExercises[$m] ?? 0) === 0);
        $below = array_filter($targetMuscles, fn ($m) => ($sessionExercises[$m] ?? 0) > 0 && ($sessionExercises[$m] ?? 0) < $min);
        if ($untouched) {
            $score += $scoring['untouched_muscle'];
            $reasons[] = sprintf('Ещё не нагружено в этой тренировке: «%s».', $this->muscleNames($untouched));
        } elseif ($below) {
            $score += $scoring['muscle_below_session_min'];
            $reasons[] = sprintf('На «%s» пока меньше %d упражнений.', $this->muscleNames($below), $min);
        }

        // Правило 5: базовые раньше изолирующих.
        if ($exercise->isCompound()) {
            $score += $scoring['compound'];
            $reasons[] = 'Базовое многосуставное — ставим в начало тренировки.';
        }

        // Правило 6: недобор недельного объёма.
        $weeklyMin = $settings->int('weekly_sets_min');
        $under = array_filter($targetMuscles, fn ($m) => ($weeklySets[$m] ?? 0) < $weeklyMin);
        if ($under) {
            $score += $scoring['weekly_undertrained'];
            $m = array_values($under)[0];
            $reasons[] = sprintf('Недобор за неделю: «%s» — %s из %d–%d подходов.', Muscle::label($m), $this->num($weeklySets[$m] ?? 0), $weeklyMin, $settings->int('weekly_sets_max'));
        }

        // Правило 3: давно не выполнявшиеся — выше.
        if ($lastUsed === null) {
            $score += $scoring['max_freshness'];
            $reasons[] = 'Ещё не выполнялось — новый стимул.';
        } else {
            $days = (int) $lastUsed->diffInDays($today);
            $fresh = min($scoring['max_freshness'], intdiv($days, $scoring['freshness_days_per_point']));
            $score += $fresh;
            if ($days >= 14) {
                $reasons[] = sprintf('Давно не выполнялось (%d дн.).', $days);
            }
        }

        // Мало времени в запасе — тяжёлые упражнения ниже.
        if ($minutesLeftAfter < 10) {
            $score -= $exercise->fatigue * $scoring['fatigue_penalty'];
        }

        return [$score, $reasons];
    }

    /**
     * Если обязательный паттерн закрыть нечем из-за ротации — предлагаем самый «давний» вариант.
     */
    private function rotationFallback(array $allowed, array $blocked, array $missingPatterns, array &$excluded): array
    {
        foreach ($missingPatterns as $pattern) {
            $hasAllowed = collect($allowed)->contains(fn (Suggestion $s) => $s->exercise->pattern === $pattern);
            if ($hasAllowed) {
                continue;
            }
            $options = collect($blocked)->filter(fn ($b) => $b['suggestion']->exercise->pattern === $pattern);
            if ($options->isEmpty()) {
                continue;
            }
            // Берём вариант, который выполнялся давнее остальных.
            $pick = $options->sortBy(fn ($b) => $b['date']->timestamp)->first();
            $suggestion = $pick['suggestion'];
            $suggestion->rotationFallback = true;
            $suggestion->reasons[] = 'Все варианты этого паттерна недавно выполнялись — предложен самый давний из них, чтобы не оставлять паттерн пустым.';
            $allowed[] = $suggestion;
            $excluded = array_values(array_filter($excluded, fn ($row) => $row['exercise']->id !== $suggestion->exercise->id));
        }

        return $allowed;
    }

    /**
     * Группировка по паттернам: сначала незакрытые обязательные, затем по лучшему score.
     *
     * @param  list<Suggestion>  $allowed
     * @return list<SuggestionGroup>
     */
    private function group(array $allowed, array $requiredPatterns, array $coveredPatterns): array
    {
        $byPattern = [];
        foreach ($allowed as $s) {
            $byPattern[$s->exercise->pattern][] = $s;
        }

        $groups = [];
        foreach ($byPattern as $pattern => $items) {
            usort($items, fn (Suggestion $a, Suggestion $b) => [$b->score, $a->exercise->name] <=> [$a->score, $b->exercise->name]);
            $groups[] = new SuggestionGroup(
                pattern: $pattern,
                label: config("training.patterns.$pattern", $pattern),
                required: in_array($pattern, $requiredPatterns, true),
                covered: in_array($pattern, $coveredPatterns, true),
                suggestions: $items,
            );
        }

        usort($groups, function (SuggestionGroup $a, SuggestionGroup $b) use ($requiredPatterns) {
            $aMissing = $a->required && ! $a->covered;
            $bMissing = $b->required && ! $b->covered;
            if ($aMissing !== $bMissing) {
                return $aMissing ? -1 : 1;
            }
            if ($aMissing) {
                // Порядок обязательных паттернов — как в шаблоне.
                return array_search($a->pattern, $requiredPatterns) <=> array_search($b->pattern, $requiredPatterns);
            }

            return $b->topScore() <=> $a->topScore();
        });

        return $groups;
    }

    private function muscleNames(array $slugs): string
    {
        return implode('», «', array_map(fn ($m) => Muscle::label($m), array_values($slugs)));
    }

    private function patternNames(array $patterns): string
    {
        return implode(', ', array_map(fn ($p) => mb_strtolower(config("training.patterns.$p", $p)), $patterns));
    }

    private function num(float $n): string
    {
        return rtrim(rtrim(number_format($n, 1, ',', ''), '0'), ',');
    }
}
