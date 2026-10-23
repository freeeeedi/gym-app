<?php

namespace Database\Seeders;

use App\Models\Exercise;
use Illuminate\Database\Seeder;

class ExerciseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (require __DIR__.'/data/exercises.php' as $row) {
            $exercise = Exercise::updateOrCreate(['slug' => $row['slug']], [
                'user_id' => null,
                'name' => $row['name'],
                'description' => $row['tip'] ?? null,
                'pattern' => $row['pattern'],
                'equipment' => $row['equipment'],
                'mechanic' => $row['mechanic'],
                'fatigue' => $row['fatigue'],
                'icon' => $row['icon'],
                'rep_min' => $row['reps'][0],
                'rep_max' => $row['reps'][1],
            ]);
            $exercise->syncMuscles($row['p'], $row['s']);
        }
    }
}
