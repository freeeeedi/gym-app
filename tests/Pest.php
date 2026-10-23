<?php

use App\Models\DayTemplate;
use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

/*
| Хелперы для тестов.
*/

function seedCatalog(): void
{
    test()->seed(DatabaseSeeder::class);
}

/**
 * Создаёт тренировку пользователя с упражнениями (по slug).
 * Для завершённых тренировок записывает рабочие подходы: planned_sets × ($weight × $reps).
 */
function makeWorkout(
    User $user,
    ?string $template = null,
    array $exercises = [],
    string $date = 'today',
    string $status = 'planned',
    float $weight = 50,
    int $reps = 10,
    int $sets = 3,
): Workout {
    $workout = Workout::create([
        'user_id' => $user->id,
        'day_template_id' => $template ? DayTemplate::where('slug', $template)->value('id') : null,
        'title' => $template ?? 'Тренировка',
        'scheduled_on' => Carbon::parse($date)->toDateString(),
        'status' => $status,
        'started_at' => $status === 'completed' ? Carbon::parse($date)->setTime(18, 0) : null,
        'finished_at' => $status === 'completed' ? Carbon::parse($date)->setTime(19, 0) : null,
    ]);

    foreach (array_values($exercises) as $i => $slug) {
        $we = $workout->workoutExercises()->create([
            'exercise_id' => Exercise::where('slug', $slug)->value('id') ?? throw new RuntimeException("Нет упражнения $slug"),
            'position' => $i,
            'planned_sets' => $sets,
        ]);
        if ($status === 'completed') {
            for ($s = 0; $s < $sets; $s++) {
                $we->sets()->create(['position' => $s, 'weight_kg' => $weight, 'reps' => $reps]);
            }
        }
    }

    return $workout->fresh();
}
