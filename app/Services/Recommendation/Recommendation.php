<?php

namespace App\Services\Recommendation;

use App\Models\Exercise;

/** Результат работы ExerciseRecommender. */
final class Recommendation
{
    /**
     * @param  list<SuggestionGroup>  $groups
     * @param  list<array{exercise: Exercise, reason: string}>  $excluded
     * @param  list<string>  $dayMuscles
     * @param  list<string>  $coveredPatterns
     * @param  list<string>  $missingPatterns  обязательные паттерны, которых ещё нет
     * @param  array<string, int>  $sessionExercises  упражнений на мышцу в тренировке
     * @param  array<string, float>  $weeklySets  подходов на мышцу за неделю с учётом этой тренировки
     */
    public function __construct(
        public readonly array $groups,
        public readonly array $excluded,
        public readonly float $currentMinutes,
        public readonly int $budgetMinutes,
        public readonly array $dayMuscles,
        public readonly array $coveredPatterns,
        public readonly array $missingPatterns,
        public readonly array $sessionExercises,
        public readonly array $weeklySets,
    ) {}

    /** @return list<Suggestion> все подсказки, по убыванию score */
    public function flat(): array
    {
        $all = [];
        foreach ($this->groups as $group) {
            array_push($all, ...$group->suggestions);
        }
        usort($all, fn (Suggestion $a, Suggestion $b) => $b->score <=> $a->score);

        return $all;
    }

    public function find(string $slug): ?Suggestion
    {
        foreach ($this->flat() as $s) {
            if ($s->exercise->slug === $slug) {
                return $s;
            }
        }

        return null;
    }

    public function excludedReason(string $slug): ?string
    {
        foreach ($this->excluded as $row) {
            if ($row['exercise']->slug === $slug) {
                return $row['reason'];
            }
        }

        return null;
    }

    public function remainingMinutes(): float
    {
        return max(0, $this->budgetMinutes - $this->currentMinutes);
    }
}
