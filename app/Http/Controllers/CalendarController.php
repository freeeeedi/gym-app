<?php

namespace App\Http\Controllers;

use App\Models\DayTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $view = $request->query('view') === 'week' ? 'week' : 'month';
        try {
            $selected = Carbon::parse($request->query('date', 'today'))->startOfDay();
        } catch (\Throwable) {
            $selected = Carbon::today();
        }

        if ($view === 'month') {
            $from = $selected->copy()->startOfMonth()->startOfWeek();
            $to = $selected->copy()->endOfMonth()->endOfWeek();
            $prev = $selected->copy()->subMonthNoOverflow()->startOfMonth();
            $next = $selected->copy()->addMonthNoOverflow()->startOfMonth();
            $title = $selected->translatedFormat('F Y');
        } else {
            $from = $selected->copy()->startOfWeek();
            $to = $selected->copy()->endOfWeek();
            $prev = $selected->copy()->subWeek();
            $next = $selected->copy()->addWeek();
            $title = $from->translatedFormat('j M').' — '.$to->translatedFormat('j M Y');
        }

        $workouts = $request->user()->workouts()
            ->with('template')
            ->withCount('workoutExercises')
            ->whereBetween('scheduled_on', [$from->toDateString(), $to->toDateString()])
            ->orderBy('scheduled_on')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($w) => $w->scheduled_on->toDateString());

        $days = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $days[] = $d->copy();
        }

        return view('calendar', [
            'view' => $view,
            'selected' => $selected,
            'days' => $days,
            'workouts' => $workouts,
            'title' => $title,
            'prev' => $prev,
            'next' => $next,
            'templates' => DayTemplate::visibleTo($request->user())->orderBy('id')->get(),
        ]);
    }
}
