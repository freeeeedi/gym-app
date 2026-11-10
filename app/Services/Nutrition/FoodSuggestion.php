<?php

namespace App\Services\Nutrition;

use App\Models\Food;

/**
 * Рекомендованный продукт: роль в рационе, очки ранжирования и причины.
 */
class FoodSuggestion
{
    /** @param list<string> $reasons */
    public function __construct(
        public readonly Food $food,
        public readonly string $role,
        public readonly float $score,
        public readonly array $reasons,
    ) {}
}
