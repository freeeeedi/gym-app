<?php

namespace App\Services\Recommendation;

use App\Models\Exercise;

/** Одна подсказка: упражнение, его вклад во время и объяснение «почему». */
final class Suggestion
{
    /**
     * @param  list<string>  $reasons
     */
    public function __construct(
        public readonly Exercise $exercise,
        public readonly int $sets,
        public readonly float $minutes,
        public readonly float $projectedMinutes,
        public int $score,
        public array $reasons,
        public bool $rotationFallback = false,
    ) {}
}
