<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExerciseRequest;
use App\Models\Exercise;
use App\Models\WorkoutExercise;
use App\Services\ProgressionAdvisor;
use App\Services\TrainingAnalytics;
use App\Services\TrainingHistory;
use App\View\Components\ExerciseIcon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExerciseController extends Controller
{
    public function index(): View
    {
        return view('exercises.index');
    }

    public function create(): View
    {
        return view('exercises.create', ['icons' => ExerciseIcon::available()]);
    }

    public function store(StoreExerciseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $exercise = $request->user()->exercises()->create([
            ...collect($data)->except(['primary', 'secondary'])->all(),
            'slug' => 'u'.$request->user()->id.'-'.Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
        ]);
        $secondary = array_values(array_diff($data['secondary'] ?? [], $data['primary']));
        $exercise->syncMuscles($data['primary'], $secondary);

        return redirect()->route('exercises.show', $exercise)->with('status', 'Упражнение добавлено в каталог.');
    }

    /** Карточка упражнения: графики, рекорды, подсказка прогрессии. */
    public function show(Request $request, Exercise $exercise, TrainingAnalytics $analytics, ProgressionAdvisor $advisor): View
    {
        $this->authorize('view', $exercise);
        $exercise->load('muscles');
        $user = $request->user();
        $history = new TrainingHistory($user);

        $sessions = $analytics->exerciseSessions($history, $exercise);
        $record = $analytics->records($history)->first(fn ($r) => $r['exercise']->id === $exercise->id);

        return view('exercises.show', [
            'exercise' => $exercise,
            'sessions' => $sessions,
            'record' => $record,
            'advice' => $advisor->advise($exercise, $history, $user->trainingSettings()->int('stagnation_sessions')),
        ]);
    }

    public function destroy(Exercise $exercise): RedirectResponse
    {
        $this->authorize('delete', $exercise);

        if (WorkoutExercise::where('exercise_id', $exercise->id)->exists()) {
            return back()->withErrors(['exercise' => 'Упражнение уже есть в тренировках — удалить его нельзя, иначе пропадёт история.']);
        }
        $exercise->delete();

        return redirect()->route('exercises.index')->with('status', 'Упражнение удалено.');
    }
}
