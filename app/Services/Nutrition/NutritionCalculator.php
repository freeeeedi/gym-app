<?php

namespace App\Services\Nutrition;

use App\Support\NutritionProfile;

/**
 * Расчёты по правилам из config/nutrition.php: ИМТ, базовый обмен, суточный расход, норма КБЖУ.
 */
class NutritionCalculator
{
    /** Индекс массы тела: вес (кг) / рост (м)², до десятых. */
    public function bmi(float $weightKg, float $heightCm): float
    {
        if ($weightKg <= 0 || $heightCm <= 0) {
            return 0.0;
        }

        return round($weightKg / ($heightCm / 100) ** 2, 1);
    }

    /** @return array{key: string, label: string} категория ИМТ по ВОЗ */
    public function bmiCategory(float $bmi): array
    {
        foreach (config('nutrition.bmi_categories') as $category) {
            if ($category['max'] === null || $bmi < $category['max']) {
                return ['key' => $category['key'], 'label' => $category['label']];
            }
        }

        throw new \LogicException('В nutrition.bmi_categories нет завершающей категории.');
    }

    /**
     * Диапазон веса, при котором ИМТ в норме (18,5–25), кг.
     *
     * @return array{0: float, 1: float}
     */
    public function normalWeightRange(float $heightCm): array
    {
        $m2 = ($heightCm / 100) ** 2;

        return [round(18.5 * $m2, 1), round(25 * $m2, 1)];
    }

    /** Базовый обмен по формуле Миффлина — Сан Жеора, ккал/сутки. */
    public function bmr(NutritionProfile $profile): float
    {
        $base = 10 * $profile->weightKg + 6.25 * $profile->heightCm - 5 * $profile->age;

        return $base + ($profile->sex === 'male' ? 5 : -161);
    }

    /** Суточный расход энергии: базовый обмен × коэффициент активности. */
    public function tdee(NutritionProfile $profile): float
    {
        return $this->bmr($profile) * config("nutrition.activity_levels.{$profile->activity}.factor");
    }

    /**
     * Вес, от которого считаются белок и жиры. При ожирении (ИМТ ≥ порога) — вес,
     * соответствующий ИМТ 25: иначе нормы на кг получаются завышенными.
     */
    public function referenceWeight(NutritionProfile $profile): float
    {
        if ($this->bmi($profile->weightKg, $profile->heightCm) < config('nutrition.reference_weight_bmi_threshold')) {
            return $profile->weightKg;
        }

        return round(config('nutrition.reference_weight_bmi') * ($profile->heightCm / 100) ** 2, 1);
    }

    /** Суточная норма под цель. null — профиль заполнен не полностью. */
    public function targets(NutritionProfile $profile): ?NutritionTargets
    {
        if (! $profile->isComplete()) {
            return null;
        }

        $goal = config("nutrition.goals.{$profile->goal}");
        $per = config('nutrition.kcal_per_gram');
        $bmr = $this->bmr($profile);
        $tdee = $this->tdee($profile);
        $notes = [];

        // Калории: расход с поправкой на цель, с округлением до 10 и безопасным минимумом.
        $kcal = (int) (round($tdee * (1 + $goal['calorie_adjustment']) / 10) * 10);
        if ($goal['calorie_adjustment'] != 0) {
            $notes[] = sprintf('Цель «%s»: %+d%% к суточному расходу.', $goal['label'], (int) round($goal['calorie_adjustment'] * 100));
        }
        $floor = config("nutrition.min_calories.{$profile->sex}");
        if ($kcal < $floor) {
            $kcal = $floor;
            $notes[] = "Норма поднята до безопасного минимума — {$floor} ккал.";
        }

        // Белок и жиры — в граммах на кг расчётного веса, углеводы — остаток калорий.
        $weight = $this->referenceWeight($profile);
        if ($weight < $profile->weightKg) {
            $notes[] = 'Белок и жиры рассчитаны от веса при ИМТ '.config('nutrition.reference_weight_bmi').' ('.format_kg($weight).' кг), а не от фактического.';
        }
        $protein = (int) round($goal['protein_per_kg'] * $weight);
        $fat = (int) round($goal['fat_per_kg'] * $weight);
        $carbs = ($kcal - $protein * $per['protein'] - $fat * $per['fat']) / $per['carbs'];

        // Углеводов слишком мало для тренировок — урезаем жиры до нижней границы.
        $minCarbs = config('nutrition.min_carbs_per_kg') * $weight;
        if ($carbs < $minCarbs) {
            $minFat = (int) round(config('nutrition.min_fat_per_kg') * $weight);
            $spare = (int) ceil(($minCarbs - $carbs) * $per['carbs'] / $per['fat']);
            $reduced = max($minFat, $fat - $spare);
            if ($reduced < $fat) {
                $fat = $reduced;
                $carbs = ($kcal - $protein * $per['protein'] - $fat * $per['fat']) / $per['carbs'];
                $notes[] = 'Жиры снижены, чтобы оставить углеводы для тренировок.';
            }
        }

        return new NutritionTargets(
            kcal: $kcal,
            protein: $protein,
            fat: $fat,
            carbs: (int) max(0, round($carbs)),
            waterMl: $this->waterMl($profile->weightKg),
            bmr: (int) round($bmr),
            tdee: (int) round($tdee),
            notes: $notes,
        );
    }

    /** Норма воды: мл на кг веса с округлением; без веса — значение по умолчанию. */
    public function waterMl(?float $weightKg): int
    {
        $water = config('nutrition.water');
        if (! $weightKg) {
            return $water['default_ml'];
        }

        return (int) (round($weightKg * $water['ml_per_kg'] / $water['round_to']) * $water['round_to']);
    }
}
