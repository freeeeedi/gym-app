<?php

namespace Database\Seeders;

use App\Enums\WorkoutStatus;
use App\Models\DayTemplate;
use App\Models\Exercise;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Демо-пользователь и 6 недель истории (пн/ср/пт: тяга, жим, ноги; варианты A/B по неделям).
 * Веса растут по двойной прогрессии; жим лёжа намеренно «застрял» — для демонстрации стагнации.
 * Запуск: php artisan app:demo
 */
class DemoSeeder extends Seeder
{
    public const EMAIL = 'demo@example.com';

    private const WEEKS = 6;

    private const PLANS = [
        'pull' => [
            'A' => ['lat-pulldown-wide', 'barbell-row', 'dumbbell-curl', 'hammer-curl'],
            'B' => ['pull-up', 'seated-cable-row', 'cable-curl', 'wrist-curl'],
        ],
        'push' => [
            'A' => ['bench-press', 'incline-dumbbell-press', 'seated-dumbbell-press', 'lateral-raise', 'rope-pushdown'],
            'B' => ['bench-press', 'machine-shoulder-press', 'pec-deck', 'cable-lateral-raise', 'skull-crusher'],
        ],
        'legs' => [
            'A' => ['back-squat', 'romanian-deadlift', 'leg-extension', 'lying-leg-curl', 'standing-calf-raise'],
            'B' => ['leg-press', 'bulgarian-split-squat', 'seated-leg-curl', 'seated-calf-raise', 'cable-crunch'],
        ],
    ];

    private const START_WEIGHTS = [
        'lat-pulldown-wide' => 50, 'barbell-row' => 50, 'dumbbell-curl' => 10, 'hammer-curl' => 12,
        'pull-up' => 0, 'seated-cable-row' => 50, 'cable-curl' => 20, 'wrist-curl' => 20,
        'bench-press' => 80, 'incline-dumbbell-press' => 22, 'seated-dumbbell-press' => 18, 'lateral-raise' => 8,
        'rope-pushdown' => 20, 'machine-shoulder-press' => 40, 'pec-deck' => 45, 'cable-lateral-raise' => 5,
        'skull-crusher' => 25, 'back-squat' => 90, 'romanian-deadlift' => 70, 'leg-extension' => 45,
        'lying-leg-curl' => 35, 'standing-calf-raise' => 60, 'leg-press' => 140, 'bulgarian-split-squat' => 14,
        'seated-leg-curl' => 40, 'seated-calf-raise' => 40, 'cable-crunch' => 35,
    ];

    /** Состояние двойной прогрессии: [slug => [weight, reps]]. */
    private array $state = [];

    public function run(): void
    {
        DB::transaction(function () {
            User::where('email', self::EMAIL)->delete();
            $user = User::create([
                'name' => 'Демо',
                'email' => self::EMAIL,
                'password' => 'password',
                'email_verified_at' => now(),
            ]);

            $templates = DayTemplate::whereIn('slug', array_keys(self::PLANS))->pluck('id', 'slug');
            $exercises = Exercise::whereIn('slug', array_keys(self::START_WEIGHTS))->get()->keyBy('slug');
            $schedule = [0 => 'pull', 2 => 'push', 4 => 'legs']; // пн, ср, пт

            $monday = Carbon::today()->startOfWeek()->subWeeks(self::WEEKS);
            for ($week = 0; $week <= self::WEEKS; $week++) {
                $variant = $week % 2 === 0 ? 'A' : 'B';
                foreach ($schedule as $offset => $type) {
                    $date = $monday->copy()->addWeeks($week)->addDays($offset);
                    if ($date->gte(Carbon::today())) {
                        continue;
                    }
                    $this->completedWorkout($user, $templates[$type], $type, $date, self::PLANS[$type][$variant], $exercises);
                }
            }

            // Пустая тренировка тяги на сегодня — для проверки подсказок, и ноги через 2 дня.
            $user->workouts()->create([
                'day_template_id' => $templates['pull'], 'title' => 'Тяга (спина и бицепс)',
                'scheduled_on' => Carbon::today(), 'status' => WorkoutStatus::Planned,
            ]);
            $user->workouts()->create([
                'day_template_id' => $templates['legs'], 'title' => 'Ноги',
                'scheduled_on' => Carbon::today()->addDays(2), 'status' => WorkoutStatus::Planned,
            ]);
        });
    }

    private function completedWorkout(User $user, int $templateId, string $type, Carbon $date, array $slugs, $exercises): void
    {
        $start = $date->copy()->setTime(19, 0);
        $workout = $user->workouts()->create([
            'day_template_id' => $templateId,
            'title' => DayTemplate::find($templateId)->name,
            'scheduled_on' => $date,
            'status' => WorkoutStatus::Completed,
            'started_at' => $start,
            'finished_at' => $start->copy()->addMinutes(50 + ($date->day % 4) * 4),
        ]);

        foreach ($slugs as $position => $slug) {
            $exercise = $exercises[$slug];
            $entry = $workout->workoutExercises()->create([
                'exercise_id' => $exercise->id, 'position' => $position, 'planned_sets' => 3,
            ]);
            foreach ($this->sets($exercise) as $i => [$weight, $reps]) {
                $entry->sets()->create(['position' => $i, 'weight_kg' => $weight, 'reps' => $reps]);
            }
        }
    }

    /** Три рабочих подхода и сдвиг состояния прогрессии. */
    private function sets(Exercise $exercise): array
    {
        $slug = $exercise->slug;
        [$weight, $reps] = $this->state[$slug] ?? [(float) self::START_WEIGHTS[$slug], $exercise->rep_min];

        if ($slug === 'bench-press') {
            // Стагнация: вес стоит, повторения колеблются.
            $sets = [[$weight, $reps], [$weight, $reps], [$weight, $reps - 1]];
            $this->state[$slug] = [$weight, $reps === $exercise->rep_min ? $reps + 1 : $exercise->rep_min];

            return $sets;
        }

        $sets = [[$weight, $reps], [$weight, $reps], [$weight, max($exercise->rep_min - 1, $reps - 1)]];
        if ($reps - 1 >= $exercise->rep_max) {
            $step = (float) config('training.weight_increments.'.$exercise->equipment, 2.5);
            $this->state[$slug] = [$weight + $step, $exercise->rep_min];
        } else {
            $this->state[$slug] = [$weight, min($exercise->rep_max + 1, $reps + 2)];
        }

        return $sets;
    }
}
