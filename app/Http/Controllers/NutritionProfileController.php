<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateNutritionProfileRequest;
use App\Services\Nutrition\NutritionCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class NutritionProfileController extends Controller
{
    /** Анкета для подбора питания и результат расчёта: ИМТ и суточная норма. */
    public function edit(Request $request, NutritionCalculator $calculator): View
    {
        $profile = $request->user()->nutritionProfile();
        $bmi = $profile->weightKg && $profile->heightCm ? $calculator->bmi($profile->weightKg, $profile->heightCm) : null;

        return view('nutrition.profile', [
            'profile' => $profile,
            'targets' => $calculator->targets($profile),
            'bmi' => $bmi,
            'bmiCategory' => $bmi ? $calculator->bmiCategory($bmi) : null,
            'normalWeight' => $profile->heightCm ? $calculator->normalWeightRange($profile->heightCm) : null,
        ]);
    }

    public function update(UpdateNutritionProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->update(['nutrition_profile' => [
            'sex' => $data['sex'],
            'age' => (int) $data['age'],
            'height_cm' => (float) $data['height_cm'],
            'activity' => $data['activity'],
            'goal' => $data['goal'],
            'restrictions' => array_values($data['restrictions'] ?? []),
        ]]);

        // Вес живёт в журнале веса: изменившееся значение — запись за сегодня.
        if ($user->currentWeight() !== (float) $data['weight_kg']) {
            $user->weightEntries()->updateOrCreate(
                ['measured_on' => Carbon::today()->toDateString()],
                ['weight_kg' => $data['weight_kg']],
            );
        }

        return back()->with('status', 'Профиль питания сохранён, норма пересчитана.');
    }
}
