<?php

namespace App\Services\Nutrition;

use App\Models\Food;
use App\Models\ShoppingListItem;
use Illuminate\Support\Collection;

/**
 * Список покупок: ингредиенты выбранных блюд, пересчитанные на нужное число порций
 * и сложенные по продуктам.
 */
class ShoppingListBuilder
{
    /**
     * У позиций должна быть загружена связь dish.ingredients.food.
     *
     * @param  Collection<int, ShoppingListItem>  $items
     * @return array<string, list<array{food: Food, grams: float}>> категория => продукты
     */
    public function build(Collection $items): array
    {
        $totals = [];
        foreach ($items as $item) {
            $factor = $item->servings / max(1, $item->dish->servings);
            foreach ($item->dish->ingredients as $ingredient) {
                $totals[$ingredient->food_id] ??= ['food' => $ingredient->food, 'grams' => 0.0];
                $totals[$ingredient->food_id]['grams'] += $ingredient->grams * $factor;
            }
        }

        $groups = [];
        foreach (array_keys(config('nutrition.food_categories')) as $category) {
            $rows = array_values(array_filter($totals, fn (array $row) => $row['food']->category === $category));
            if ($rows === []) {
                continue;
            }
            usort($rows, fn (array $a, array $b) => $a['food']->name <=> $b['food']->name);
            $groups[$category] = array_map(
                fn (array $row) => ['food' => $row['food'], 'grams' => $this->roundGrams($row['grams'])],
                $rows,
            );
        }

        return $groups;
    }

    /** Для магазина точность до грамма не нужна: до 5 г, от 100 г — до 10 г, вверх. */
    private function roundGrams(float $grams): float
    {
        $step = $grams >= 100 ? 10 : 5;

        return ceil($grams / $step) * $step;
    }
}
