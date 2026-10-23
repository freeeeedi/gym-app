<?php

namespace Database\Seeders;

use App\Models\DayTemplate;
use Illuminate\Database\Seeder;

class DayTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach (require __DIR__.'/data/templates.php' as $row) {
            $template = DayTemplate::updateOrCreate(['slug' => $row['slug']], [
                'user_id' => null,
                'name' => $row['name'],
                'description' => $row['description'],
                'patterns' => $row['patterns'],
            ]);
            $template->syncMuscles($row['muscles']);
        }
    }
}
