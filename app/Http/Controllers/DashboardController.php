<?php

namespace App\Http\Controllers;

use App\Models\DayTemplate;
use App\Services\TrainingAnalytics;
use App\Services\TrainingHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, TrainingAnalytics $analytics): View
    {
        $user = $request->user();
        $today = Carbon::today();

        $workouts = $user->workouts()
            ->with('template')
            ->withCount('workoutExercises')
            ->whereBetween('scheduled_on', [$today->copy()->subDays(1), $today->copy()->addDays(7)])
            ->orderBy('scheduled_on')
            ->get();

        $active = $user->workouts()->where('status', 'in_progress')->latest('started_at')->first();
        $lastCompleted = $user->workouts()->where('status', 'completed')->latest('scheduled_on')->first();

        $history = new TrainingHistory($user, limit: 40);
        $volume = $analytics->weeklyMuscleVolume($user, $history, 2);

        return view('dashboard', [
            'today' => $today,
            'todayWorkouts' => $workouts->filter(fn ($w) => $w->scheduled_on->isSameDay($today)),
            'upcoming' => $workouts->filter(fn ($w) => $w->scheduled_on->gt($today) && ! $w->isCompleted()),
            'active' => $active,
            'lastCompleted' => $lastCompleted,
            'templates' => DayTemplate::visibleTo($user)->orderByRaw('user_id is null')->orderBy('id')->get(),
            'volume' => $volume,
            'settings' => $user->trainingSettings(),
        ]);
    }
}
