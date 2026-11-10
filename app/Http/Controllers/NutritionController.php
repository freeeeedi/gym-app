<?php

namespace App\Http\Controllers;

use App\Services\Nutrition\NutritionCalculator;
use App\Services\Nutrition\NutritionDiary;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class NutritionController extends Controller
{
    /** Дневник питания (Livewire-компонент MealDiary). */
    public function diary(): View
    {
        return view('nutrition.diary');
    }

    /** Каталог продуктов и рекомендации (Livewire-компонент FoodCatalog). */
    public function foods(): View
    {
        return view('nutrition.foods');
    }

    /** Статистика: КБЖУ по дням недели в сравнении с нормой, вес и ИМТ. */
    public function stats(Request $request, NutritionDiary $diary, NutritionCalculator $calculator): View
    {
        $user = $request->user();
        try {
            $selected = Carbon::parse($request->query('week', 'today'))->startOfDay();
        } catch (\Throwable) {
            $selected = Carbon::today();
        }

        $profile = $user->nutritionProfile();
        $targets = $calculator->targets($profile);
        $week = $diary->week($user, $selected, $targets);

        $weights = $user->weightEntries()
            ->where('measured_on', '>=', Carbon::today()->subDays(config('nutrition.weight_chart_days'))->toDateString())
            ->orderBy('measured_on')
            ->get();
        $bmi = $profile->weightKg && $profile->heightCm ? $calculator->bmi($profile->weightKg, $profile->heightCm) : null;

        return view('nutrition.stats', [
            'week' => $week,
            'targets' => $targets,
            'profile' => $profile,
            'prev' => $week['start']->copy()->subWeek(),
            'next' => $week['start']->copy()->addWeek(),
            'isCurrentWeek' => $week['start']->equalTo(Carbon::today()->startOfWeek()),
            'weights' => $weights,
            'bmi' => $bmi,
            'bmiCategory' => $bmi ? $calculator->bmiCategory($bmi) : null,
        ]);
    }
}
