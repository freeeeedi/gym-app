<?php

namespace App\Services;

use Carbon\CarbonInterface;

/** Подсказка по двойной прогрессии для упражнения. */
final class ProgressionAdvice
{
    /**
     * @param  list<array{weight: float, reps: int}>  $lastSets
     */
    public function __construct(
        public readonly CarbonInterface $lastDate,
        public readonly array $lastSets,
        public readonly string $action, // increase | reps | hold
        public readonly ?float $suggestedWeight,
        public readonly string $message,
        public readonly bool $stagnating = false,
        public readonly ?string $stagnationMessage = null,
    ) {}

    public function lastSetsLabel(): string
    {
        return implode(', ', array_map(fn ($s) => format_kg($s['weight']).'×'.$s['reps'], $this->lastSets));
    }

    /** Вес, с которого стоит начать следующую тренировку. */
    public function nextWeight(): ?float
    {
        return $this->suggestedWeight ?? ($this->lastSets ? max(array_column($this->lastSets, 'weight')) : null);
    }
}
