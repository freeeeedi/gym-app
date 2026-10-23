<?php

namespace Database\Seeders;

use App\Models\Muscle;
use App\Models\WarmupExercise;
use Illuminate\Database\Seeder;

class WarmupExerciseSeeder extends Seeder
{
    public function run(): void
    {
        $ids = Muscle::idsBySlug();

        foreach (require __DIR__.'/data/warmups.php' as $row) {
            $warmup = WarmupExercise::updateOrCreate(['slug' => $row['slug']], [
                'name' => $row['name'],
                'type' => $row['type'],
                'minutes' => $row['minutes'],
                'reps' => $row['reps'],
                'joints' => $row['joints'],
                'description' => $row['description'],
                'icon' => $row['icon'],
            ]);
            $warmup->muscles()->sync(collect($row['muscles'])->map(fn ($s) => $ids[$s])->all());
        }
    }
}
