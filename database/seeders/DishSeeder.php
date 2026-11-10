<?php

namespace Database\Seeders;

use App\Models\Dish;
use App\Models\Food;
use Illuminate\Database\Seeder;
use RuntimeException;

class DishSeeder extends Seeder
{
    public function run(): void
    {
        $foodIds = Food::whereNull('user_id')->pluck('id', 'slug');

        foreach (require __DIR__.'/data/dishes.php' as $row) {
            $dish = Dish::updateOrCreate(['slug' => $row['slug']], [
                'name' => $row['name'],
                'meals' => $row['meals'],
                'servings' => $row['servings'],
                'cook_minutes' => $row['minutes'] ?? null,
                'recipe' => $row['recipe'] ?? null,
            ]);

            // Ингредиенты пересобираются целиком: рецепт в файле — источник истины.
            $dish->ingredients()->delete();
            $position = 0;
            foreach ($row['ingredients'] as $slug => $grams) {
                $dish->ingredients()->create([
                    'food_id' => $foodIds[$slug] ?? throw new RuntimeException("Блюдо {$row['slug']}: нет продукта $slug"),
                    'grams' => $grams,
                    'position' => $position++,
                ]);
            }
        }
    }
}
