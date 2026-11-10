<?php

namespace Database\Seeders;

use App\Models\Food;
use Illuminate\Database\Seeder;

class FoodSeeder extends Seeder
{
    public function run(): void
    {
        foreach (require __DIR__.'/data/foods.php' as $row) {
            [$slug, $name, $category, $kcal, $protein, $fat, $carbs, $tags] = $row;

            Food::updateOrCreate(['slug' => $slug], [
                'user_id' => null,
                'name' => $name,
                'category' => $category,
                'kcal' => $kcal,
                'protein' => $protein,
                'fat' => $fat,
                'carbs' => $carbs,
                'tags' => $tags,
                'serving_g' => $row[8] ?? null,
                'serving_name' => $row[9] ?? null,
            ]);
        }
    }
}
