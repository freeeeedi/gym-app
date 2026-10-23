<?php

namespace App\Http\Controllers;

use App\Enums\WorkoutStatus;
use App\Http\Requests\StoreWorkoutRequest;
use App\Http\Requests\UpdateWorkoutRequest;
use App\Models\DayTemplate;
use App\Models\Workout;
use App\Services\TrainingAnalytics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WorkoutController extends Controller
{
    public function store(StoreWorkoutRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $template = isset($data['day_template_id']) ? DayTemplate::find($data['day_template_id']) : null;

        $workout = $request->user()->workouts()->create([
            'day_template_id' => $template?->id,
            'title' => filled($data['title'] ?? null) ? $data['title'] : ($template?->name ?? 'Тренировка'),
            'scheduled_on' => $data['scheduled_on'],
            'status' => WorkoutStatus::Planned,
        ]);

        return redirect()->route('workouts.show', $workout);
    }

    /** План тренировки: выбор упражнений, подсказки, разминка. */
    public function show(Request $request, Workout $workout): View|RedirectResponse
    {
        $this->authorize('view', $workout);
        $workout->load('template');

        return view('workouts.show', [
            'workout' => $workout,
            'templates' => DayTemplate::visibleTo($request->user())->orderBy('id')->get(),
        ]);
    }

    public function update(UpdateWorkoutRequest $request, Workout $workout): RedirectResponse
    {
        $data = $request->validated();

        if (isset($data['status'])) {
            $status = WorkoutStatus::from($data['status']);
            if ($status === WorkoutStatus::InProgress && ! $workout->started_at) {
                $data['started_at'] = now();
            }
            if ($status === WorkoutStatus::Completed && ! $workout->finished_at) {
                $data['started_at'] = $workout->started_at ?? now();
                $data['finished_at'] = now();
            }
            if ($status === WorkoutStatus::Planned) {
                $data['started_at'] = null;
                $data['finished_at'] = null;
            }
        }
        $workout->update($data);

        return back()->with('status', isset($data['scheduled_on']) ? 'Тренировка перенесена на '.$workout->scheduled_on->translatedFormat('j F').'.' : 'Сохранено.');
    }

    public function destroy(Workout $workout): RedirectResponse
    {
        $this->authorize('delete', $workout);
        $date = $workout->scheduled_on;
        $workout->delete();

        return redirect()->route('calendar', ['date' => $date->toDateString()])->with('status', 'Тренировка удалена.');
    }

    public function start(Workout $workout): RedirectResponse
    {
        $this->authorize('update', $workout);

        if (! $workout->workoutExercises()->exists()) {
            return back()->withErrors(['workout' => 'Добавьте хотя бы одно упражнение.']);
        }
        if (! $workout->isInProgress()) {
            $workout->update(['status' => WorkoutStatus::InProgress, 'started_at' => $workout->started_at ?? now(), 'finished_at' => null]);
        }

        return redirect()->route('workouts.session', $workout);
    }

    /** Проведение тренировки: ввод подходов, таймеры. */
    public function session(Workout $workout): View|RedirectResponse
    {
        $this->authorize('view', $workout);

        if ($workout->status === WorkoutStatus::Planned) {
            return redirect()->route('workouts.show', $workout);
        }

        return view('workouts.session', ['workout' => $workout]);
    }

    public function finish(Workout $workout): RedirectResponse
    {
        $this->authorize('update', $workout);

        DB::transaction(function () use ($workout) {
            // Невыполненные упражнения не попадают в историю (и не влияют на ротацию).
            $workout->workoutExercises()->whereDoesntHave('sets')->delete();
            $workout->update([
                'status' => WorkoutStatus::Completed,
                'started_at' => $workout->started_at ?? now(),
                'finished_at' => now(),
            ]);
        });

        return redirect()->route('workouts.summary', $workout);
    }

    public function summary(Workout $workout, TrainingAnalytics $analytics): View|RedirectResponse
    {
        $this->authorize('view', $workout);

        if (! $workout->isCompleted()) {
            return redirect()->route('workouts.show', $workout);
        }
        $workout->load('user', 'workoutExercises.exercise', 'workoutExercises.sets');

        return view('workouts.summary', [
            'workout' => $workout,
            'summary' => $analytics->summary($workout),
        ]);
    }
}
