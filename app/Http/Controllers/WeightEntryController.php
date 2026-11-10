<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class WeightEntryController extends Controller
{
    /** Запись веса на дату; повторная запись за тот же день заменяет прежнюю. */
    public function store(Request $request): RedirectResponse
    {
        $request->merge(['weight_kg' => str_replace(',', '.', (string) $request->input('weight_kg'))]);
        $data = $request->validate([
            'measured_on' => ['required', 'date', 'before_or_equal:today'],
            'weight_kg' => ['required', 'numeric', 'between:30,300'],
        ]);

        $request->user()->weightEntries()->updateOrCreate(
            ['measured_on' => Carbon::parse($data['measured_on'])->toDateString()],
            ['weight_kg' => $data['weight_kg']],
        );

        return back()->with('status', 'Вес записан.');
    }

    public function destroy(Request $request, int $entry): RedirectResponse
    {
        $request->user()->weightEntries()->findOrFail($entry)->delete();

        return back()->with('status', 'Запись веса удалена.');
    }
}
