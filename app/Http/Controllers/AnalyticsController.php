<?php

namespace App\Http\Controllers;

use App\Services\TrainingAnalytics;
use App\Services\TrainingHistory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request, TrainingAnalytics $analytics): View
    {
        $user = $request->user();
        $history = new TrainingHistory($user);

        return view('analytics.index', [
            'volume' => $analytics->weeklyMuscleVolume($user, $history, 6),
            'records' => $analytics->records($history),
            'recent' => $history->workouts()->take(20),
            'settings' => $user->trainingSettings(),
        ]);
    }
}
