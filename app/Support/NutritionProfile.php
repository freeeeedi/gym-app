<?php

namespace App\Support;

/**
 * Профиль питания пользователя: users.nutrition_profile + последний вес из weight_entries.
 */
class NutritionProfile
{
    /** @param list<string> $restrictions ключи config('nutrition.restrictions') */
    public function __construct(
        public readonly ?string $sex = null,
        public readonly ?int $age = null,
        public readonly ?float $heightCm = null,
        public readonly ?float $weightKg = null,
        public readonly string $activity = 'moderate',
        public readonly string $goal = 'maintain',
        public readonly array $restrictions = [],
    ) {}

    public static function fromArray(array $data, ?float $weightKg = null): self
    {
        $activity = $data['activity'] ?? null;
        $goal = $data['goal'] ?? null;

        return new self(
            sex: isset(config('nutrition.sexes')[$data['sex'] ?? '']) ? $data['sex'] : null,
            age: isset($data['age']) ? (int) $data['age'] : null,
            heightCm: isset($data['height_cm']) ? (float) $data['height_cm'] : null,
            weightKg: $weightKg,
            activity: isset(config('nutrition.activity_levels')[$activity]) ? $activity : 'moderate',
            goal: isset(config('nutrition.goals')[$goal]) ? $goal : 'maintain',
            restrictions: array_values(array_intersect(
                (array) ($data['restrictions'] ?? []),
                array_keys(config('nutrition.restrictions')),
            )),
        );
    }

    /** Достаточно ли данных для расчёта нормы. */
    public function isComplete(): bool
    {
        return $this->sex !== null && $this->age > 0 && $this->heightCm > 0 && $this->weightKg > 0;
    }

    public function goalLabel(): string
    {
        return config("nutrition.goals.{$this->goal}.label");
    }

    public function activityLabel(): string
    {
        return config("nutrition.activity_levels.{$this->activity}.label");
    }

    /** @return list<string> названия выбранных ограничений */
    public function restrictionLabels(): array
    {
        return array_map(fn (string $key) => config("nutrition.restrictions.$key.label"), $this->restrictions);
    }

    /**
     * Признаки продуктов, исключённые ограничениями пользователя.
     *
     * @return list<string>
     */
    public function excludedTags(): array
    {
        $tags = [];
        foreach ($this->restrictions as $key) {
            $tags = [...$tags, ...config("nutrition.restrictions.$key.excludes", [])];
        }

        return array_values(array_unique($tags));
    }

    /** Можно ли продукт с такими признаками при ограничениях пользователя. */
    public function allows(array $tags): bool
    {
        return array_intersect($tags, $this->excludedTags()) === [];
    }
}
