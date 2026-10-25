<?php

namespace App\Livewire;

use App\Models\Exercise;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Services\ExerciseRecommender;
use App\Services\TrainingHistory;
use App\Services\WarmupPlanner;
use App\Services\WorkoutDurationEstimator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * План тренировки: выбранные упражнения, умные подсказки, каталог, разминка.
 */
class WorkoutPlanner extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public int $workoutId;

    public string $tab = 'suggestions';

    public function mount(Workout $workout): void
    {
        $this->authorize('view', $workout);
        $this->workoutId = $workout->id;
    }

    #[On('exercise-picked')]
    public function add(int $exerciseId): void
    {
        $workout = $this->editableWorkout();
        $exercise = Exercise::visibleTo($workout->user)->findOrFail($exerciseId);

        if ($workout->workoutExercises()->where('exercise_id', $exercise->id)->exists()) {
            return;
        }

        DB::transaction(function () use ($workout, $exercise) {
            $items = $workout->workoutExercises()->with('exercise')->get();
            // Порядок: базовые раньше изолирующих — новое базовое встаёт после последнего базового.
            $insertAt = $exercise->isCompound()
                ? $items->filter(fn ($we) => $we->exercise->isCompound())->count()
                : $items->count();

            $new = $workout->workoutExercises()->create([
                'exercise_id' => $exercise->id,
                'position' => 0,
                'planned_sets' => $workout->user->trainingSettings()->defaultSets($exercise->isCompound()),
            ]);
            $items->splice($insertAt, 0, [$new]);
            $this->renumber($items);
        });
    }

    public function remove(int $workoutExerciseId): void
    {
        $workout = $this->editableWorkout();
        $workout->workoutExercises()->whereKey($workoutExerciseId)->delete();
        $this->renumber($workout->workoutExercises()->get());
    }

    public function move(int $workoutExerciseId, int $direction): void
    {
        $items = $this->editableWorkout()->workoutExercises()->get()->values();
        $index = $items->search(fn ($we) => $we->id === $workoutExerciseId);
        $target = $index + ($direction < 0 ? -1 : 1);
        if ($index === false || $target < 0 || $target >= $items->count()) {
            return;
        }
        $moved = $items[$index];
        $items[$index] = $items[$target];
        $items[$target] = $moved;
        $this->renumber($items);
    }

    public function setSets(int $workoutExerciseId, int $sets): void
    {
        $sets = max(1, min(10, $sets));
        $this->editableWorkout()->workoutExercises()->whereKey($workoutExerciseId)->update(['planned_sets' => $sets]);
    }

    public function render(ExerciseRecommender $recommender, WarmupPlanner $warmupPlanner)
    {
        $workout = Workout::with(['user', 'template.muscles', 'workoutExercises.exercise.muscles', 'workoutExercises.sets'])
            ->findOrFail($this->workoutId);
        $editable = ! $workout->isCompleted();
        $settings = $workout->user->trainingSettings();
        $estimator = new WorkoutDurationEstimator($settings);

        // История загружается один раз и нужна и подсказкам, и разминке.
        $history = new TrainingHistory($workout->user, $workout->scheduled_on, $workout->id);
        $recommendation = $editable ? $recommender->recommend($workout, $history) : null;

        return view('livewire.workout-planner', [
            'workout' => $workout,
            'editable' => $editable,
            'recommendation' => $recommendation,
            'warmup' => $warmupPlanner->plan($workout, $history),
            'estimator' => $estimator,
            'totalMinutes' => $estimator->totalMinutes($workout->workoutExercises->map(fn ($we) => [$we->exercise, $we->planned_sets])),
            'budget' => $settings->int('time_budget_minutes'),
            'perPattern' => config('training.suggestions_per_pattern'),
        ]);
    }

    private function editableWorkout(): Workout
    {
        $workout = Workout::with('user')->findOrFail($this->workoutId);
        $this->authorize('update', $workout);
        abort_if($workout->isCompleted(), 403, 'Завершённую тренировку изменить нельзя.');

        return $workout;
    }

    private function renumber($items): void
    {
        foreach ($items->values() as $i => $we) {
            if ($we->position !== $i) {
                WorkoutExercise::whereKey($we->id)->update(['position' => $i]);
            }
        }
    }
}
