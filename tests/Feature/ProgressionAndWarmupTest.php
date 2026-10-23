<?php

use App\Models\Exercise;
use App\Models\User;
use App\Services\ProgressionAdvisor;
use App\Services\TrainingAnalytics;
use App\Services\TrainingHistory;
use App\Services\WarmupPlanner;
use App\Support\OneRepMax;

beforeEach(function () {
    seedCatalog();
    $this->user = User::factory()->create();
});

it('считает 1ПМ по формуле Эпли', function () {
    expect(OneRepMax::epley(100, 1))->toBe(100.0)
        ->and(OneRepMax::epley(100, 10))->toBe(133.3)
        ->and(OneRepMax::epley(0, 10))->toBe(0.0);
});

it('двойная прогрессия: все подходы на верхней границе — предлагает повысить вес', function () {
    // Тяга блока сидя: диапазон 8–12, шаг для блока 2,5 кг.
    makeWorkout($this->user, 'pull', ['seated-cable-row'], '-3 days', 'completed', weight: 60, reps: 12);
    $exercise = Exercise::where('slug', 'seated-cable-row')->first();

    $advice = app(ProgressionAdvisor::class)->advise($exercise, new TrainingHistory($this->user));

    expect($advice->action)->toBe('increase')
        ->and($advice->suggestedWeight)->toBe(62.5)
        ->and($advice->message)->toContain('62,5');
});

it('двойная прогрессия: не все подходы на верхней границе — держать вес', function () {
    makeWorkout($this->user, 'pull', ['seated-cable-row'], '-3 days', 'completed', weight: 60, reps: 10);
    $exercise = Exercise::where('slug', 'seated-cable-row')->first();

    $advice = app(ProgressionAdvisor::class)->advise($exercise, new TrainingHistory($this->user));

    expect($advice->action)->toBe('reps')->and($advice->suggestedWeight)->toBe(60.0);
});

it('определяет стагнацию и рекомендует разгрузку', function () {
    makeWorkout($this->user, 'pull', ['seated-cable-row'], '-20 days', 'completed', weight: 60, reps: 10);
    makeWorkout($this->user, 'pull', ['seated-cable-row'], '-15 days', 'completed', weight: 60, reps: 9);
    makeWorkout($this->user, 'pull', ['seated-cable-row'], '-10 days', 'completed', weight: 60, reps: 10);
    makeWorkout($this->user, 'pull', ['seated-cable-row'], '-5 days', 'completed', weight: 60, reps: 8);
    $exercise = Exercise::where('slug', 'seated-cable-row')->first();

    $advice = app(ProgressionAdvisor::class)->advise($exercise, new TrainingHistory($this->user));

    expect($advice->stagnating)->toBeTrue()->and($advice->stagnationMessage)->toContain('разгрузочная');

    makeWorkout($this->user, 'pull', ['seated-cable-row'], '-1 days', 'completed', weight: 62.5, reps: 10);
    $advice = app(ProgressionAdvisor::class)->advise($exercise, new TrainingHistory($this->user));
    expect($advice->stagnating)->toBeFalse();
});

it('разминка формируется под мышцы выбранных упражнений с процентами от рабочего веса', function () {
    makeWorkout($this->user, 'legs', ['back-squat'], '-4 days', 'completed', weight: 100, reps: 6);
    $workout = makeWorkout($this->user, 'legs', ['back-squat', 'lying-leg-curl', 'standing-calf-raise']);

    $plan = app(WarmupPlanner::class)->plan($workout);

    expect($plan->focusMuscles)->toContain('quads', 'glutes')
        ->and($plan->general->type)->toBe('general')
        ->and($plan->mobility)->not->toBeEmpty()
        ->and($plan->rampExercise->slug)->toBe('back-squat')
        ->and($plan->workingWeight)->toBe(100.0)
        ->and(array_column($plan->rampSets, 'weight'))->toBe([40.0, 60.0, 80.0]);

    $muscles = collect($plan->mobility)->flatMap->muscleSlugs()->unique();
    expect($muscles)->toContain('quads');
});

it('разминка для верха тела без истории — подходы без веса', function () {
    $workout = makeWorkout($this->user, 'push', ['bench-press', 'lateral-raise']);

    $plan = app(WarmupPlanner::class)->plan($workout);

    expect($plan->focusMuscles)->toContain('chest', 'shoulders')
        ->and($plan->workingWeight)->toBeNull()
        ->and($plan->rampSets[0]['weight'])->toBeNull()
        ->and(collect($plan->mobility)->flatMap->muscleSlugs())->toContain('chest', 'shoulders');
});

it('итоги тренировки показывают объём и новые рекорды', function () {
    makeWorkout($this->user, 'push', ['bench-press'], '-7 days', 'completed', weight: 80, reps: 6);
    $workout = makeWorkout($this->user, 'push', ['bench-press'], 'today', 'completed', weight: 82.5, reps: 6);

    $summary = app(TrainingAnalytics::class)->summary($workout);

    expect($summary['volume'])->toEqual(82.5 * 6 * 3)
        ->and($summary['sets'])->toBe(3)
        ->and($summary['duration'])->toBe(60)
        ->and(collect($summary['records'])->pluck('type')->all())->toBe(['Рабочий вес', 'Расчётный 1ПМ']);
});

it('недельный объём выявляет недогруженные группы', function () {
    $lastWeek = now()->startOfWeek()->subWeek();
    makeWorkout($this->user, 'pull', ['barbell-row', 'pull-up', 'seated-cable-row'], $lastWeek->toDateString(), 'completed', sets: 3);

    $volume = app(TrainingAnalytics::class)->weeklyMuscleVolume($this->user, new TrainingHistory($this->user), 4, now()->startOfWeek()->addDay());

    expect($volume['sets']['back'][2])->toEqual(9.0)
        ->and($volume['undertrained'])->not->toContain('back')
        ->and($volume['undertrained'])->toContain('chest', 'quads');
});
