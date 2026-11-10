<?php

namespace App\Services\Nutrition;

use App\Models\Dish;
use App\Models\Food;
use App\Support\NutritionProfile;
use Illuminate\Support\Collection;

/**
 * Подбор продуктов и блюд по правилам из config('nutrition.recommendation'):
 * 1. Исключаются продукты с признаками, запрещёнными ограничениями пользователя,
 *    и продукты «не на каждый день».
 * 2. Продукт получает роль: овощи и фрукты, источник белка, сложные углеводы, полезные жиры.
 *    Продукты без роли не рекомендуются.
 * 3. Внутри роли продукты ранжируются очками, зависящими от цели; каждое правило даёт причину.
 * Блюдо исключается, если запрещён хотя бы один ингредиент.
 */
class NutritionRecommender
{
    /**
     * @param  Collection<int, Food>  $foods
     * @return array{groups: array<string, list<FoodSuggestion>>, excluded: int}
     */
    public function foods(Collection $foods, NutritionProfile $profile): array
    {
        $rules = config('nutrition.recommendation');
        $allowed = $foods->filter(fn (Food $food) => $profile->allows($food->tagList()));

        $groups = array_fill_keys(array_keys($rules['roles']), []);
        foreach ($allowed as $food) {
            $suggestion = $this->suggestFood($food, $profile->goal);
            if ($suggestion) {
                $groups[$suggestion->role][] = $suggestion;
            }
        }

        foreach ($groups as $role => $items) {
            usort($items, fn (FoodSuggestion $a, FoodSuggestion $b) => [$b->score, $a->food->name] <=> [$a->score, $b->food->name]);
            $groups[$role] = array_slice($items, 0, $rules['foods_per_role']);
        }

        return ['groups' => $groups, 'excluded' => $foods->count() - $allowed->count()];
    }

    /** Роль продукта в рационе или null, если продукт не рекомендуется. */
    public function foodRole(Food $food): ?string
    {
        $rules = config('nutrition.recommendation');
        $macros = $food->per100();

        if ($food->hasTag('occasional') || $macros->kcal <= 0) {
            return null;
        }
        if (in_array($food->category, $rules['produce_categories'], true)) {
            return 'produce';
        }
        if ($macros->share('protein') >= $rules['protein_share']) {
            return 'protein';
        }
        if (in_array($food->category, $rules['fat_categories'], true) && $macros->share('fat') >= $rules['fat_share']) {
            return 'fat';
        }
        if (in_array($food->category, $rules['carb_categories'], true)) {
            return 'carbs';
        }

        return null;
    }

    public function suggestFood(Food $food, string $goal): ?FoodSuggestion
    {
        $role = $this->foodRole($food);
        if ($role === null) {
            return null;
        }

        $rules = config('nutrition.recommendation');
        $weights = $rules['scoring'][$goal];
        $macros = $food->per100();
        $reasons = [];

        // Белок на 100 ккал — главный показатель «плотности» белка.
        $score = $macros->protein / $macros->kcal * 100 * $weights['protein_density'];
        if ($macros->protein >= $rules['high_protein_g']) {
            $reasons[] = 'Много белка: '.format_kg($macros->protein).' г на 100 г';
        }

        if ($macros->kcal <= $rules['low_calorie_kcal']) {
            $score += $weights['low_calorie'];
            if ($weights['low_calorie'] > 0) {
                $reasons[] = 'Низкая калорийность: '.format_kg($macros->kcal).' ккал на 100 г';
            }
        }

        // Калорийность оценивается у всего, кроме источников белка: там важнее белок.
        if ($role !== 'protein' && $macros->kcal >= $rules['calorie_dense_kcal']) {
            $score += $weights['calorie_dense'];
            if ($weights['calorie_dense'] > 0) {
                $reasons[] = 'Калорийный продукт: проще добрать норму';
            } elseif ($weights['calorie_dense'] < 0) {
                $reasons[] = 'Калорийно — отмеряйте порцию';
            }
        }

        if ($role === 'carbs') {
            $score += $weights['complex_carbs'];
            $reasons[] = 'Сложные углеводы — энергия для тренировок';
        }
        if ($role === 'produce') {
            $score += $weights['produce'];
            $reasons[] = 'Клетчатка и витамины';
        }
        if ($role === 'fat') {
            $reasons[] = 'Ненасыщенные жиры';
        }
        if ($food->category === 'fish' && $macros->fat >= 6) {
            $reasons[] = 'Омега-3 жирные кислоты';
        }

        return new FoodSuggestion($food, $role, round($score, 1), $reasons);
    }

    /**
     * Блюда, разрешённые ограничениями, от самых подходящих цели.
     * У блюд должна быть загружена связь ingredients.food.
     *
     * @param  Collection<int, Dish>  $dishes
     * @return array{suggestions: list<DishSuggestion>, excluded: int}
     */
    public function dishes(Collection $dishes, NutritionProfile $profile, ?NutritionTargets $targets = null): array
    {
        $rules = config('nutrition.recommendation');
        $weights = $rules['scoring'][$profile->goal];
        $dailyKcal = $targets?->kcal ?? 2000;
        $allowed = $dishes->filter(fn (Dish $dish) => $profile->allows($dish->tagList()));

        $suggestions = [];
        foreach ($allowed as $dish) {
            $macros = $dish->perServing();
            $reasons = [];
            $score = $macros->kcal > 0 ? $macros->protein / $macros->kcal * 100 * $weights['protein_density'] : 0;

            if ($macros->protein >= $rules['dish_high_protein_g']) {
                $score += 10;
                $reasons[] = 'Много белка: '.format_kg(round($macros->protein)).' г на порцию';
            }
            if ($profile->goal === 'cut' && $macros->kcal <= $rules['dish_light_share'] * $dailyKcal) {
                $score += 15;
                $reasons[] = 'Лёгкая порция: '.format_kg(round($macros->kcal)).' ккал';
            }
            if ($profile->goal === 'gain' && $macros->kcal >= $rules['dish_hearty_share'] * $dailyKcal) {
                $score += 15;
                $reasons[] = 'Сытная порция: '.format_kg(round($macros->kcal)).' ккал — проще добрать норму';
            }

            $suggestions[] = new DishSuggestion($dish, $macros, round($score, 1), $reasons);
        }

        usort($suggestions, fn (DishSuggestion $a, DishSuggestion $b) => [$b->score, $a->dish->name] <=> [$a->score, $b->dish->name]);

        return ['suggestions' => $suggestions, 'excluded' => $dishes->count() - $allowed->count()];
    }
}
