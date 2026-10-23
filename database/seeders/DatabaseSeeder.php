<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Справочники: мышцы, каталог упражнений, шаблоны дней, разминка.
     * Демо-данные подключаются отдельно: php artisan app:demo.
     */
    public function run(): void
    {
        $this->call([
            MuscleSeeder::class,
            ExerciseSeeder::class,
            DayTemplateSeeder::class,
            WarmupExerciseSeeder::class,
        ]);
    }
}
