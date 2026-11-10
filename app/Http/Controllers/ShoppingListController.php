<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use App\Services\Nutrition\ShoppingListBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShoppingListController extends Controller
{
    /** Выбранные блюда и общий список продуктов для них. */
    public function index(Request $request, ShoppingListBuilder $builder): View
    {
        $items = $request->user()->shoppingListItems()->with('dish.ingredients.food')->orderBy('id')->get();

        return view('nutrition.shopping', [
            'items' => $items,
            'groups' => $builder->build($items),
        ]);
    }

    /** Добавить блюдо в список; повторное добавление меняет число порций. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'dish_id' => ['required', 'integer', 'exists:dishes,id'],
            'servings' => ['nullable', 'integer', 'between:1,30'],
        ]);
        $dish = Dish::findOrFail($data['dish_id']);

        $request->user()->shoppingListItems()->updateOrCreate(
            ['dish_id' => $dish->id],
            ['servings' => $data['servings'] ?? $dish->servings],
        );

        return back()->with('status', "«{$dish->name}» добавлено в список покупок.");
    }

    public function update(Request $request, int $item): RedirectResponse
    {
        $data = $request->validate(['servings' => ['required', 'integer', 'between:1,30']]);
        $request->user()->shoppingListItems()->findOrFail($item)->update($data);

        return back();
    }

    public function destroy(Request $request, int $item): RedirectResponse
    {
        $request->user()->shoppingListItems()->findOrFail($item)->delete();

        return back();
    }

    public function clear(Request $request): RedirectResponse
    {
        $request->user()->shoppingListItems()->delete();

        return back()->with('status', 'Список покупок очищен.');
    }
}
