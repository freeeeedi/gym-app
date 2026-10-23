<?php

namespace App\Support;

class OneRepMax
{
    /**
     * Расчётный максимум на 1 повторение по формуле Эпли: w × (1 + r / 30).
     * Точность падает при r > 10, поэтому это ориентир, а не измерение.
     */
    public static function epley(float $weight, int $reps): float
    {
        if ($reps <= 0 || $weight <= 0) {
            return 0.0;
        }
        if ($reps === 1) {
            return round($weight, 1);
        }

        return round($weight * (1 + $reps / 30), 1);
    }
}
