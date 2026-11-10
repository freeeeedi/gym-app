<?php

namespace App\Support;

/**
 * КБЖУ: калории, белки, жиры, углеводы. Неизменяемое значение.
 */
final class Macros
{
    public function __construct(
        public readonly float $kcal = 0.0,
        public readonly float $protein = 0.0,
        public readonly float $fat = 0.0,
        public readonly float $carbs = 0.0,
    ) {}

    /** Из массива или модели с полями kcal, protein, fat, carbs. */
    public static function from(array|object $source): self
    {
        $source = (object) (is_array($source) ? $source : $source->only(['kcal', 'protein', 'fat', 'carbs']));

        return new self((float) $source->kcal, (float) $source->protein, (float) $source->fat, (float) $source->carbs);
    }

    /** @param iterable<self> $items */
    public static function sum(iterable $items): self
    {
        $total = new self;
        foreach ($items as $item) {
            $total = $total->plus($item);
        }

        return $total;
    }

    public function plus(self $other): self
    {
        return new self(
            $this->kcal + $other->kcal,
            $this->protein + $other->protein,
            $this->fat + $other->fat,
            $this->carbs + $other->carbs,
        );
    }

    public function minus(self $other): self
    {
        return $this->plus($other->times(-1));
    }

    public function times(float $factor): self
    {
        return new self($this->kcal * $factor, $this->protein * $factor, $this->fat * $factor, $this->carbs * $factor);
    }

    /** Округление до десятых — так значения хранятся в базе. */
    public function rounded(int $precision = 1): self
    {
        return new self(
            round($this->kcal, $precision),
            round($this->protein, $precision),
            round($this->fat, $precision),
            round($this->carbs, $precision),
        );
    }

    public function isZero(): bool
    {
        return $this->kcal == 0.0 && $this->protein == 0.0 && $this->fat == 0.0 && $this->carbs == 0.0;
    }

    /** Доля калорий из нутриента (0..1): protein | fat | carbs. */
    public function share(string $nutrient): float
    {
        if ($this->kcal <= 0) {
            return 0.0;
        }

        return min(1.0, $this->{$nutrient} * config("nutrition.kcal_per_gram.$nutrient") / $this->kcal);
    }

    /** @return array{kcal: float, protein: float, fat: float, carbs: float} */
    public function toArray(): array
    {
        return ['kcal' => $this->kcal, 'protein' => $this->protein, 'fat' => $this->fat, 'carbs' => $this->carbs];
    }
}
