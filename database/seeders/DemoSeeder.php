<?php

namespace Database\Seeders;

use App\Enums\WorkoutStatus;
use App\Models\DayTemplate;
use App\Models\Exercise;
use App\Models\Food;
use App\Models\User;
use App\Services\Nutrition\NutritionDiary;
use App\Support\Macros;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Демо-пользователь и 6 недель истории (пн/ср/пт: тяга, жим, ноги; варианты A/B по неделям).
 * Веса растут по двойной прогрессии; жим лёжа намеренно «застрял» — для демонстрации стагнации.
 * Питание: профиль (набор массы), вес за 6 недель и дневник за последнюю неделю.
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

    /** Рацион демо-дней: приём пищи => [slug продукта => граммы]; варианты чередуются. */
    private const MENUS = [
        [
            'breakfast' => ['oats' => 70, 'milk-2-5' => 250, 'banana' => 120, 'egg' => 110],
            'lunch' => ['chicken-breast-cooked' => 180, 'buckwheat-cooked' => 250, 'cucumber' => 100, 'tomato' => 120, 'olive-oil' => 10],
            'snack' => ['cottage-cheese-5' => 200, 'walnuts' => 20, 'apple' => 170],
            'dinner' => ['salmon' => 150, 'rice-cooked' => 250, 'broccoli' => 150],
        ],
        [
            'breakfast' => ['egg' => 165, 'bread-wholegrain' => 60, 'hard-cheese' => 30, 'tomato' => 120],
            'lunch' => ['beef-lean' => 180, 'pasta-cooked' => 300, 'frozen-vegetables' => 150],
            'snack' => ['whey-protein' => 30, 'milk-2-5' => 250, 'banana' => 120],
            'dinner' => ['turkey-breast' => 180, 'potato-boiled' => 300, 'cabbage' => 150, 'sunflower-oil' => 10],
        ],
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

            $this->nutrition($user);
        });
    }

    /** Профиль питания, вес по неделям и дневник за 6 прошедших дней + сегодняшний завтрак. */
    private function nutrition(User $user): void
    {
        $user->update(['nutrition_profile' => [
            'sex' => 'male', 'age' => 28, 'height_cm' => 180, 'activity' => 'moderate', 'goal' => 'gain', 'restrictions' => [],
        ]]);

        for ($week = self::WEEKS; $week >= 0; $week--) {
            $user->weightEntries()->create([
                'measured_on' => Carbon::today()->subWeeks($week),
                'weight_kg' => 78 + (self::WEEKS - $week) * 0.3,
            ]);
        }

        $diary = app(NutritionDiary::class);
        $foods = Food::whereNull('user_id')->get()->keyBy('slug');

        for ($daysAgo = 6; $daysAgo >= 0; $daysAgo--) {
            $date = Carbon::today()->subDays($daysAgo);
            foreach (self::MENUS[$daysAgo % 2] as $meal => $items) {
                // Сегодня записан только завтрак — остаток до нормы виден на главной.
                if ($daysAgo === 0 && $meal !== 'breakfast') {
                    continue;
                }
                foreach ($items as $slug => $grams) {
                    $diary->addFood($user, $date, $meal, $foods[$slug], $grams);
                }
            }
            if ($daysAgo > 0) {
                $user->waterLogs()->create(['logged_on' => $date, 'ml' => 1500 + ($daysAgo % 3) * 500]);
            }
        }

        // Пример ручного ввода: пирожное из кофейни, которого нет в каталоге.
        $diary->addCustom($user, Carbon::today()->subDays(2), 'snack', 'Эклер из кофейни', new Macros(330, 5, 22, 28), 90);
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
