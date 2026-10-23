<?php

namespace Database\Seeders;

use App\Models\Muscle;
use Illuminate\Database\Seeder;

class MuscleSeeder extends Seeder
{
    public function run(): void
    {
        $sort = 0;
        foreach (config('training.muscles') as $slug => $name) {
            Muscle::updateOrCreate(['slug' => $slug], ['name' => $name, 'sort' => $sort++]);
        }
    }
}
