<?php

namespace App\Livewire;

use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use App\Services\ProgressionAdvisor;
use App\Services\TrainingHistory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Проведение тренировки: ввод подходов (вес × повторения), прошлый результат, подсказка прогрессии.
 * Таймеры работают на клиенте (Alpine), сервер сообщает только длительность отдыха.
 */
class WorkoutSession extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public int $workoutId;

    public ?int $activeId = null;

    public string $weight = '';

    public string $reps = '';

    public function mount(Workout $workout): void
    {
        $this->authorize('view', $workout);
        $this->workoutId = $workout->id;
        $first = $workout->workoutExercises()->whereDoesntHave('sets')->value('id')
            ?? $workout->workoutExercises()->value('id');
        if ($first) {
            $this->open($first);
        }
    }

    public function open(int $workoutExerciseId): void
    {
        $entry = $this->entry($workoutExerciseId);
        $this->activeId = $entry->id;
        $this->prefill($entry);
    }

    public function addSet(): void
    {
        $this->weight = str_replace(',', '.', trim($this->weight));
        $data = $this->validate([
            'weight' => ['required', 'numeric', 'min:0', 'max:1000'],
            'reps' => ['required', 'integer', 'min:1', 'max:200'],
        ], attributes: ['weight' => 'вес', 'reps' => 'повторения']);

        $this->logSet((float) $data['weight'], (int) $data['reps']);
    }

    /** «Копировать предыдущий подход» — одно нажатие. */
    public function copyLast(): void
    {
        $entry = $this->entry($this->activeId);
        $last = $entry->sets->last();
        if (! $last) {
            $this->addSet();

            return;
        }
        $this->logSet((float) $last->weight_kg, $last->reps);
    }

    public function updateSet(int $setId, string $field, string $value): void
    {
        $set = $this->ownedSet($setId);
        $rules = ['weight_kg' => ['numeric', 'min:0', 'max:1000'], 'reps' => ['integer', 'min:1', 'max:200']];
        if (! isset($rules[$field])) {
            return;
        }
        validator([$field => str_replace(',', '.', $value)], [$field => ['required', ...$rules[$field]]])->validate();
        $set->update([$field => str_replace(',', '.', $value)]);
    }

    public function deleteSet(int $setId): void
    {
        $this->ownedSet($setId)->delete();
    }

    public function render(ProgressionAdvisor $advisor)
    {
        $workout = Workout::with(['user', 'workoutExercises.exercise.muscles', 'workoutExercises.sets'])->findOrFail($this->workoutId);
        $settings = $workout->user->trainingSettings();
        $history = new TrainingHistory($workout->user, $workout->scheduled_on, $workout->id);

        $advice = [];
        foreach ($workout->workoutExercises as $we) {
            $advice[$we->id] = $advisor->advise($we->exercise, $history, $settings->int('stagnation_sessions'));
        }
        $active = $workout->workoutExercises->firstWhere('id', $this->activeId);

        return view('livewire.workout-session', [
            'workout' => $workout,
            'active' => $active,
            'advice' => $advice,
            'restSeconds' => $active ? $settings->restSeconds($active->exercise->isCompound()) : 90,
            'step' => $active ? (float) config('training.weight_increments.'.$active->exercise->equipment, 2.5) : 2.5,
            'editable' => ! $workout->isCompleted(),
        ]);
    }

    private function logSet(float $weight, int $reps): void
    {
        $entry = $this->entry($this->activeId);
        abort_if($entry->workout->isCompleted(), 403);

        $entry->sets()->create([
            'position' => ($entry->sets->max('position') ?? -1) + 1,
            'weight_kg' => $weight,
            'reps' => $reps,
        ]);
        $this->weight = format_number_input($weight);
        $this->reps = (string) $reps;

        $rest = $entry->workout->user->trainingSettings()->restSeconds($entry->exercise->isCompound());
        $this->dispatch('set-logged', seconds: $rest);
    }

    /** Предзаполнение: последний подход сегодня → рекомендация прогрессии → начало диапазона. */
    private function prefill(WorkoutExercise $entry): void
    {
        $last = $entry->sets->last();
        if ($last) {
            $this->weight = format_number_input((float) $last->weight_kg);
            $this->reps = (string) $last->reps;

            return;
        }
        $history = new TrainingHistory($entry->workout->user, $entry->workout->scheduled_on, $entry->workout_id, 30);
        $advice = app(ProgressionAdvisor::class)->advise($entry->exercise, $history);
        $this->weight = $advice?->nextWeight() !== null ? format_number_input($advice->nextWeight()) : '';
        $this->reps = (string) ($advice && $advice->action !== 'increase' ? ($advice->lastSets[0]['reps'] ?? $entry->exercise->rep_min) : $entry->exercise->rep_min);
    }

    private function entry(?int $id): WorkoutExercise
    {
        $entry = WorkoutExercise::with(['workout.user', 'exercise', 'sets'])
            ->where('workout_id', $this->workoutId)
            ->findOrFail($id);
        $this->authorize('update', $entry->workout);

        return $entry;
    }

    private function ownedSet(int $setId): WorkoutSet
    {
        $set = WorkoutSet::with('workoutExercise.workout')->findOrFail($setId);
        abort_unless($set->workoutExercise->workout_id === $this->workoutId, 404);
        $this->authorize('update', $set->workoutExercise->workout);
        abort_if($set->workoutExercise->workout->isCompleted(), 403);

        return $set;
    }
}
