<?php

namespace App\Support;

/**
 * Настройки тренировок пользователя поверх config('training.defaults').
 */
class TrainingSettings
{
    private array $values;

    public function __construct(array $overrides = [])
    {
        $defaults = config('training.defaults');
        $this->values = array_merge($defaults, array_intersect_key($overrides, $defaults));
    }

    public function get(string $key): mixed
    {
        return $this->values[$key];
    }

    public function int(string $key): int
    {
        return (int) $this->values[$key];
    }

    public function bool(string $key): bool
    {
        return (bool) $this->values[$key];
    }

    public function all(): array
    {
        return $this->values;
    }

    public function restSeconds(bool $compound): int
    {
        return $this->int($compound ? 'rest_compound_seconds' : 'rest_isolation_seconds');
    }

    public function defaultSets(bool $compound): int
    {
        return $this->int($compound ? 'sets_compound' : 'sets_isolation');
    }
}
