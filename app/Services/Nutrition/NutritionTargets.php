<?php

namespace App\Services\Nutrition;

use App\Support\Macros;

/**
 * Суточная норма: КБЖУ, вода и промежуточные величины расчёта.
 */
class NutritionTargets
{
    /** @param list<string> $notes пояснения, как получена норма */
    public function __construct(
        public readonly int $kcal,
        public readonly int $protein,
        public readonly int $fat,
        public readonly int $carbs,
        public readonly int $waterMl,
        public readonly int $bmr,
        public readonly int $tdee,
        public readonly array $notes = [],
    ) {}

    public function macros(): Macros
    {
        return new Macros($this->kcal, $this->protein, $this->fat, $this->carbs);
    }
}
