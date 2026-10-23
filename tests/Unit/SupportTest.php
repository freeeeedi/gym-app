<?php

use App\Support\OneRepMax;
use App\Support\TrainingSettings;

it('считает 1ПМ по формуле Эпли', function () {
    expect(OneRepMax::epley(100, 1))->toBe(100.0)
        ->and(OneRepMax::epley(100, 10))->toBe(133.3)
        ->and(OneRepMax::epley(0, 10))->toBe(0.0);
});

it('объединяет настройки пользователя с умолчаниями и игнорирует лишние ключи', function () {
    $settings = new TrainingSettings(['time_budget_minutes' => 75, 'unknown' => 1]);

    expect($settings->int('time_budget_minutes'))->toBe(75)
        ->and($settings->int('rotation_window'))->toBe(3)
        ->and($settings->all())->not->toHaveKey('unknown')
        ->and($settings->restSeconds(true))->toBe(150);
});

it('форматирует килограммы по-русски', function () {
    expect(format_kg(62.5))->toBe('62,5')->and(format_kg(60.0))->toBe('60')->and(format_number_input(62.5))->toBe('62.5');
});
