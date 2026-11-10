<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use App\Services\Nutrition\NutritionCalculator;
use App\Services\Nutrition\NutritionDiary;
use App\Services\Nutrition\NutritionRecommender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DishController extends Controller
{
    /** Блюда, подходящие под ограничения, от самых подходящих цели. */
    public function index(Request $request, NutritionRecommender $recommender, NutritionCalculator $calculator): View
    {
        $user = $request->user();
        $profile = $user->nutritionProfile();
        $meals = config('nutrition.meals');
        $meal = isset($meals[$request->query('meal')]) ? $request->query('meal') : null;

        $dishes = Dish::with('ingredients.food')->orderBy('name')->get()
            ->when($meal, fn ($list) => $list->filter(fn (Dish $dish) => in_array($meal, $dish->meals ?? [], true))->values());

        return view('nutrition.dishes.index', [
            'result' => $recommender->dishes($dishes, $profile, $calculator->targets($profile)),
            'profile' => $profile,
            'meal' => $meal,
            'meals' => $meals,
            'inShoppingList' => $user->shoppingListItems()->pluck('dish_id')->all(),
        ]);
    }

    public function show(Request $request, Dish $dish, NutritionCalculator $calculator): View
    {
        $dish->load('ingredients.food');
        $user = $request->user();
        $profile = $user->nutritionProfile();

        return view('nutrition.dishes.show', [
            'dish' => $dish,
            'macros' => $dish->perServing(),
            'targets' => $calculator->targets($profile),
            'allowed' => $profile->allows($dish->tagList()),
            'shoppingItem' => $user->shoppingListItems()->where('dish_id', $dish->id)->first(),
            'defaultMeal' => in_array('lunch', $dish->meals ?? [], true) ? 'lunch' : ($dish->meals[0] ?? 'snack'),
        ]);
    }

    /** Записать порции блюда в дневник. */
    public function log(Request $request, Dish $dish, NutritionDiary $diary): RedirectResponse
    {
        $request->merge(['servings' => str_replace(',', '.', (string) $request->input('servings', '1'))]);
        $data = $request->validate([
            'meal' => ['required', Rule::in(array_keys(config('nutrition.meals')))],
            'servings' => ['required', 'numeric', 'between:0.25,20'],
            'date' => ['nullable', 'date'],
        ]);

        $date = Carbon::parse($data['date'] ?? 'today');
        $diary->addDish($request->user(), $date, $data['meal'], $dish->load('ingredients.food'), (float) $data['servings']);

        return redirect()
            ->route('nutrition.diary', $date->isToday() ? [] : ['date' => $date->toDateString()])
            ->with('status', "«{$dish->name}» записано в дневник.");
    }
}
