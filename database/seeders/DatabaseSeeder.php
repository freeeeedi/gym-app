<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Справочники: мышцы, каталог упражнений, шаблоны дней, разминка, продукты и блюда.
     * Демо-данные подключаются отдельно: php artisan app:demo.
     */
    public function run(): void
    {
        $this->call([
            MuscleSeeder::class,
            ExerciseSeeder::class,
            DayTemplateSeeder::class,
            WarmupExerciseSeeder::class,
            FoodSeeder::class,
            DishSeeder::class,
        ]);
    }
}
