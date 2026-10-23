<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Срез истории завершённых тренировок пользователя (загружается одним запросом с eager loading).
 */
class TrainingHistory
{
    /** @var Collection<int, Workout> от новых к старым */
    private Collection $workouts;

    public function __construct(
        private readonly User $user,
        ?CarbonInterface $until = null,
        ?int $exceptWorkoutId = null,
        int $limit = 150,
    ) {
        $this->workouts = Workout::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->when($until, fn ($q) => $q->whereDate('scheduled_on', '<=', $until))
            ->when($exceptWorkoutId, fn ($q) => $q->whereKeyNot($exceptWorkoutId))
            ->with(['workoutExercises.exercise.muscles', 'workoutExercises.sets'])
            ->orderByDesc('scheduled_on')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function workouts(): Collection
    {
        return $this->workouts;
    }

    /** Дата последнего выполнения для каждого упражнения: [exercise_id => Carbon]. */
    public function lastUsedDates(): array
    {
        $dates = [];
        foreach ($this->workouts as $workout) {
            foreach ($workout->workoutExercises as $we) {
                $dates[$we->exercise_id] ??= $workout->scheduled_on;
            }
        }

        return $dates;
    }

    /**
     * Упражнения из последних $window тренировок, в которых мышца была основной.
     *
     * @return array<int, CarbonInterface> [exercise_id => дата]
     */
    public function recentExercisesForMuscle(string $muscle, int $window): array
    {
        $result = [];
        $seen = 0;
        foreach ($this->workouts as $workout) {
            $hits = $workout->workoutExercises->filter(
                fn (WorkoutExercise $we) => in_array($muscle, $we->exercise->primaryMuscles(), true)
            );
            if ($hits->isEmpty()) {
                continue;
            }
            foreach ($hits as $we) {
                $result[$we->exercise_id] ??= $workout->scheduled_on;
            }
            if (++$seen >= $window) {
                break;
            }
        }

        return $result;
    }

    /**
     * Рабочие подходы по мышцам за период [from, to].
     *
     * @return array<string, float>
     */
    public function setsPerMuscle(CarbonInterface $from, CarbonInterface $to, bool $secondaryAsHalf = true): array
    {
        $sets = [];
        foreach ($this->workouts as $workout) {
            if ($workout->scheduled_on->lt($from->copy()->startOfDay()) || $workout->scheduled_on->gt($to->copy()->endOfDay())) {
                continue;
            }
            foreach ($workout->workoutExercises as $we) {
                $count = $we->sets->where('is_warmup', false)->count();
                self::addSets($sets, $we->exercise->primaryMuscles(), $we->exercise->secondaryMuscles(), $count, $secondaryAsHalf);
            }
        }

        return $sets;
    }

    /** Добавляет подходы упражнения к счётчику мышц (вспомогательные — как половина). */
    public static function addSets(array &$sets, array $primary, array $secondary, float $count, bool $secondaryAsHalf): void
    {
        foreach ($primary as $m) {
            $sets[$m] = ($sets[$m] ?? 0) + $count;
        }
        if ($secondaryAsHalf) {
            foreach ($secondary as $m) {
                $sets[$m] = ($sets[$m] ?? 0) + $count / 2;
            }
        }
    }

    /**
     * Сессии конкретного упражнения от новых к старым: [workout, WorkoutExercise].
     *
     * @return Collection<int, array{workout: Workout, entry: WorkoutExercise}>
     */
    public function sessionsFor(int $exerciseId): Collection
    {
        return $this->workouts
            ->map(fn (Workout $w) => ['workout' => $w, 'entry' => $w->workoutExercises->firstWhere('exercise_id', $exerciseId)])
            ->filter(fn ($row) => $row['entry'] && $row['entry']->sets->where('is_warmup', false)->isNotEmpty())
            ->values();
    }
}
