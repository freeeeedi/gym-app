<?php

namespace App\Services\Nutrition;

use App\Models\Dish;
use App\Support\Macros;

/**
 * Блюдо с КБЖУ порции, очками ранжирования под цель и причинами.
 */
class DishSuggestion
{
    /** @param list<string> $reasons */
    public function __construct(
        public readonly Dish $dish,
        public readonly Macros $perServing,
        public readonly float $score,
        public readonly array $reasons,
    ) {}
}
