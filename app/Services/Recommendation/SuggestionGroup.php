<?php

namespace App\Services\Recommendation;

/** Подсказки, сгруппированные по паттерну движения. */
final class SuggestionGroup
{
    /**
     * @param  list<Suggestion>  $suggestions  отсортированы по убыванию score
     */
    public function __construct(
        public readonly string $pattern,
        public readonly string $label,
        public readonly bool $required,
        public readonly bool $covered,
        public readonly array $suggestions,
    ) {}

    public function topScore(): int
    {
        return $this->suggestions[0]->score ?? 0;
    }
}
