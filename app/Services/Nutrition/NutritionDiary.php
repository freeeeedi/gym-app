<?php

namespace App\Services\Nutrition;

use App\Models\Dish;
use App\Models\Food;
use App\Models\MealEntry;
use App\Models\MealTemplate;
use App\Models\User;
use App\Support\Macros;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Дневник питания: записи, суммы КБЖУ за день и неделю, сравнение с нормой.
 */
class NutritionDiary
{
    /**
     * День: записи по приёмам пищи и суммы.
     *
     * @return array{date: Carbon, meals: array<string, array{label: string, entries: Collection<int, MealEntry>, totals: Macros}>, totals: Macros, count: int}
     */
    public function day(User $user, CarbonInterface $date): array
    {
        $entries = $user->mealEntries()
            ->where('eaten_on', $date->toDateString())
            ->orderBy('id')
            ->get()
            ->groupBy('meal');

        $meals = [];
        foreach (config('nutrition.meals') as $meal => $label) {
            $items = $entries->get($meal, collect());
            $meals[$meal] = ['label' => $label, 'entries' => $items, 'totals' => $this->sum($items)];
        }

        return [
            'date' => Carbon::parse($date->toDateString()),
            'meals' => $meals,
            'totals' => Macros::sum(array_column($meals, 'totals')),
            'count' => $entries->flatten(1)->count(),
        ];
    }

    /** Сумма КБЖУ за день. */
    public function dayTotals(User $user, CarbonInterface $date): Macros
    {
        return $this->sum($user->mealEntries()->where('eaten_on', $date->toDateString())->get());
    }

    /**
     * Неделя (пн–вс), в которую попадает дата: суммы по дням, итог, среднее и сравнение с нормой.
     * Недельная норма — суточная × 7. Среднее считается по дням, где есть записи.
     *
     * @return array{start: Carbon, end: Carbon, days: list<array{date: Carbon, totals: Macros, logged: bool}>, total: Macros, logged_days: int, average: Macros, target: ?Macros, remaining: ?Macros}
     */
    public function week(User $user, CarbonInterface $anyDay, ?NutritionTargets $targets = null): array
    {
        $start = Carbon::parse($anyDay->toDateString())->startOfWeek();
        $end = $start->copy()->addDays(6);

        $byDate = $user->mealEntries()
            ->whereBetween('eaten_on', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn (MealEntry $entry) => $entry->eaten_on->toDateString());

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $start->copy()->addDays($i);
            $items = $byDate->get($date->toDateString(), collect());
            $days[] = ['date' => $date, 'totals' => $this->sum($items), 'logged' => $items->isNotEmpty()];
        }

        $total = Macros::sum(array_column($days, 'totals'));
        $logged = count(array_filter(array_column($days, 'logged')));
        $target = $targets?->macros()->times(7);

        return [
            'start' => $start,
            'end' => $end,
            'days' => $days,
            'total' => $total,
            'logged_days' => $logged,
            'average' => $logged > 0 ? $total->times(1 / $logged)->rounded() : new Macros,
            'target' => $target,
            'remaining' => $target?->minus($total)->rounded(),
        ];
    }

    /** Остаток до нормы (отрицательные значения — перебор). */
    public function remaining(Macros $target, Macros $eaten): Macros
    {
        return $target->minus($eaten)->rounded();
    }

    public function addFood(User $user, CarbonInterface $date, string $meal, Food $food, float $grams): MealEntry
    {
        return $this->store($user, $date, $meal, $food->name, $food->macrosFor($grams), [
            'food_id' => $food->id, 'grams' => $grams,
        ]);
    }

    /** Блюдо записывается порциями; ингредиенты должны быть загружены. */
    public function addDish(User $user, CarbonInterface $date, string $meal, Dish $dish, float $servings = 1): MealEntry
    {
        return $this->store($user, $date, $meal, $dish->name, $dish->perServing()->times($servings), [
            'dish_id' => $dish->id, 'servings' => $servings,
        ]);
    }

    /** Свой продукт: название и КБЖУ за всю порцию; граммы — если известны. */
    public function addCustom(User $user, CarbonInterface $date, string $meal, string $name, Macros $macros, ?float $grams = null): MealEntry
    {
        return $this->store($user, $date, $meal, $name, $macros, ['grams' => $grams]);
    }

    /** Меняет граммовку (или число порций) записи с пропорциональным пересчётом КБЖУ. */
    public function rescale(MealEntry $entry, float $amount): MealEntry
    {
        $field = $entry->grams !== null ? 'grams' : 'servings';
        $current = (float) $entry->{$field};
        if ($current <= 0 || $amount <= 0) {
            return $entry;
        }

        $entry->update([
            $field => $amount,
            ...$entry->macros()->times($amount / $current)->rounded()->toArray(),
        ]);

        return $entry;
    }

    /**
     * Копирует все записи одного дня в другой (например, вчерашний день в сегодняшний).
     *
     * @return int число скопированных записей
     */
    public function copyDay(User $user, CarbonInterface $from, CarbonInterface $to): int
    {
        $entries = $user->mealEntries()->where('eaten_on', $from->toDateString())->orderBy('id')->get();

        DB::transaction(function () use ($entries, $to) {
            foreach ($entries as $entry) {
                $copy = $entry->replicate();
                $copy->eaten_on = $to->toDateString();
                $copy->save();
            }
        });

        return $entries->count();
    }

    /** Сохраняет записи приёма пищи как шаблон. */
    public function saveTemplate(User $user, string $name, Collection $entries): MealTemplate
    {
        return DB::transaction(function () use ($user, $name, $entries) {
            $template = $user->mealTemplates()->create(['name' => $name]);
            foreach ($entries as $entry) {
                $template->items()->create($entry->only([
                    'food_id', 'dish_id', 'name', 'grams', 'servings', 'kcal', 'protein', 'fat', 'carbs',
                ]));
            }

            return $template;
        });
    }

    /** Добавляет позиции шаблона в приём пищи. Связь items должна быть загружена. */
    public function applyTemplate(User $user, MealTemplate $template, CarbonInterface $date, string $meal): int
    {
        DB::transaction(function () use ($user, $template, $date, $meal) {
            foreach ($template->items as $item) {
                $user->mealEntries()->create([
                    ...$item->only(['food_id', 'dish_id', 'name', 'grams', 'servings', 'kcal', 'protein', 'fat', 'carbs']),
                    'eaten_on' => $date->toDateString(),
                    'meal' => $meal,
                ]);
            }
        });

        return $template->items->count();
    }

    /**
     * Недавно использованные продукты, от самых свежих.
     *
     * @return Collection<int, Food>
     */
    public function recentFoods(User $user, ?int $limit = null): Collection
    {
        $ids = $user->mealEntries()
            ->whereNotNull('food_id')
            ->orderByDesc('id')
            ->limit(300)
            ->pluck('food_id')
            ->unique()
            ->take($limit ?? config('nutrition.recent_foods'))
            ->values();

        $foods = Food::visibleTo($user)->whereIn('id', $ids)->get()->keyBy('id');

        return $ids->map(fn ($id) => $foods->get($id))->filter()->values();
    }

    /** Граммовка для предзаполнения: как в прошлый раз → типичная порция → 100 г. */
    public function suggestedGrams(User $user, Food $food): float
    {
        $last = $user->mealEntries()->where('food_id', $food->id)->orderByDesc('id')->value('grams');

        return (float) ($last ?? $food->serving_g ?? 100);
    }

    /** @param iterable<MealEntry> $entries */
    private function sum(iterable $entries): Macros
    {
        $macros = [];
        foreach ($entries as $entry) {
            $macros[] = $entry->macros();
        }

        return Macros::sum($macros)->rounded();
    }

    private function store(User $user, CarbonInterface $date, string $meal, string $name, Macros $macros, array $extra): MealEntry
    {
        return $user->mealEntries()->create([
            'eaten_on' => $date->toDateString(),
            'meal' => $meal,
            'name' => $name,
            ...$macros->rounded()->toArray(),
            ...$extra,
        ]);
    }
}
