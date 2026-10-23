<?php

use App\Models\User;
use App\Services\ExerciseRecommender;
use App\Services\Recommendation\Suggestion;
use App\Services\Recommendation\SuggestionGroup;

beforeEach(function () {
    seedCatalog();
    $this->user = User::factory()->create();
    $this->recommender = app(ExerciseRecommender::class);
});

function patterns(array $groups): array
{
    return array_map(fn (SuggestionGroup $g) => $g->pattern, $groups);
}

function slugs(array $suggestions): array
{
    return array_map(fn (Suggestion $s) => $s->exercise->slug, $suggestions);
}

it('день тяги, ничего не выбрано: предлагает тяговые упражнения по всем паттернам дня', function () {
    $workout = makeWorkout($this->user, 'pull');

    $result = $this->recommender->recommend($workout);

    // Обязательные паттерны идут первыми и в порядке шаблона.
    expect(array_slice(patterns($result->groups), 0, 3))->toBe(['vertical_pull', 'horizontal_pull', 'elbow_flexion'])
        ->and($result->missingPatterns)->toBe(['vertical_pull', 'horizontal_pull', 'elbow_flexion']);

    $all = slugs($result->flat());
    expect($all)->toContain('pull-up', 'assisted-pull-up', 'lat-pulldown-wide', 'seated-cable-row', 'barbell-row', 'barbell-curl')
        ->not->toContain('bench-press', 'back-squat', 'lateral-raise');

    // Каждая подсказка объяснена.
    foreach ($result->flat() as $s) {
        expect($s->reasons)->not->toBeEmpty();
    }
});

it('выбрана горизонтальная тяга: первой предлагается вертикальная с объяснением', function () {
    $workout = makeWorkout($this->user, 'pull', ['seated-cable-row']);

    $result = $this->recommender->recommend($workout);
    $first = $result->groups[0];

    expect($first->pattern)->toBe('vertical_pull')
        ->and($result->coveredPatterns)->toContain('horizontal_pull')
        ->and($result->missingPatterns)->not->toContain('horizontal_pull')
        ->and(slugs($first->suggestions))->toContain('assisted-pull-up', 'pull-up', 'band-pull-up');

    $reasons = implode(' ', $first->suggestions[0]->reasons);
    expect($reasons)->toContain('Вертикальная тяга')->toContain('горизонтальная тяга');

    // Горизонтальная тяга больше не обязательна: её группа ниже вертикальной.
    $horizontal = collect($result->groups)->firstWhere('pattern', 'horizontal_pull');
    expect($horizontal->topScore())->toBeLessThan($first->topScore());
});

it('базовые упражнения ранжируются выше изолирующих при прочих равных', function () {
    $result = $this->recommender->recommend(makeWorkout($this->user, 'pull'));
    $vertical = collect($result->groups)->firstWhere('pattern', 'vertical_pull');

    $isolationIndex = array_search('straight-arm-pulldown', slugs($vertical->suggestions));
    foreach (array_slice($vertical->suggestions, 0, $isolationIndex) as $s) {
        expect($s->exercise->isCompound())->toBeTrue();
    }
    expect($isolationIndex)->toBe(count($vertical->suggestions) - 1);
});

it('ротация исключает упражнения из последних N тренировок на мышцу', function () {
    makeWorkout($this->user, 'pull', ['lat-pulldown-wide', 'barbell-row'], '-10 days', 'completed');
    makeWorkout($this->user, 'pull', ['assisted-pull-up'], '-6 days', 'completed');
    makeWorkout($this->user, 'pull', ['seated-cable-row'], '-3 days', 'completed');

    $result = $this->recommender->recommend(makeWorkout($this->user, 'pull'));
    $all = slugs($result->flat());

    expect($all)->not->toContain('lat-pulldown-wide', 'barbell-row', 'assisted-pull-up', 'seated-cable-row')
        ->and($all)->toContain('pull-up', 'dumbbell-row')
        ->and($result->excludedReason('lat-pulldown-wide'))->toContain('Ротация');
});

it('ротация учитывает только последние N тренировок (окно настраивается)', function () {
    makeWorkout($this->user, 'pull', ['lat-pulldown-wide'], '-20 days', 'completed');
    makeWorkout($this->user, 'pull', ['pull-up'], '-10 days', 'completed');
    makeWorkout($this->user, 'pull', ['dumbbell-row'], '-3 days', 'completed');

    $this->user->update(['training_settings' => ['rotation_window' => 2]]);
    $all = slugs($this->recommender->recommend(makeWorkout($this->user, 'pull'))->flat());

    expect($all)->toContain('lat-pulldown-wide')->not->toContain('pull-up', 'dumbbell-row');
});

it('давно не выполнявшиеся упражнения ранжируются выше недавних', function () {
    makeWorkout($this->user, 'pull', ['machine-row'], '-40 days', 'completed');
    makeWorkout($this->user, 'pull', ['t-bar-row'], '-12 days', 'completed');
    // Заполняем окно ротации другими тренировками на спину.
    makeWorkout($this->user, 'pull', ['pull-up'], '-9 days', 'completed');
    makeWorkout($this->user, 'pull', ['band-pull-up'], '-6 days', 'completed');
    makeWorkout($this->user, 'pull', ['lat-pulldown-close'], '-3 days', 'completed');

    $result = $this->recommender->recommend(makeWorkout($this->user, 'pull'));

    expect($result->find('machine-row')->score)->toBeGreaterThan($result->find('t-bar-row')->score);
});

it('если весь паттерн заблокирован ротацией, предлагает самый давний вариант', function () {
    $vertical = ['pull-up', 'band-pull-up', 'assisted-pull-up', 'lat-pulldown-wide', 'lat-pulldown-close', 'straight-arm-pulldown'];
    makeWorkout($this->user, 'pull', array_slice($vertical, 0, 2), '-9 days', 'completed');
    makeWorkout($this->user, 'pull', array_slice($vertical, 2, 2), '-6 days', 'completed');
    makeWorkout($this->user, 'pull', array_slice($vertical, 4, 2), '-3 days', 'completed');

    $result = $this->recommender->recommend(makeWorkout($this->user, 'pull'));
    $group = collect($result->groups)->firstWhere('pattern', 'vertical_pull');

    expect($group->suggestions)->toHaveCount(1)
        ->and($group->suggestions[0]->rotationFallback)->toBeTrue()
        ->and(slugs($group->suggestions)[0])->toBeIn(array_slice($vertical, 0, 2));

    $this->user->update(['training_settings' => ['rotation_fallback' => false]]);
    $strict = $this->recommender->recommend(makeWorkout($this->user, 'pull'));
    expect(collect($strict->groups)->firstWhere('pattern', 'vertical_pull'))->toBeNull();
});

it('бюджет времени не превышается', function () {
    $workout = makeWorkout($this->user, 'pull', ['barbell-row', 'pull-up', 'barbell-curl']);

    $result = $this->recommender->recommend($workout);

    expect($result->budgetMinutes)->toBe(60)
        ->and($result->currentMinutes)->toBeLessThan(60);
    foreach ($result->flat() as $s) {
        expect($s->projectedMinutes)->toBeLessThanOrEqual(60.0);
    }
    expect($result->flat())->not->toBeEmpty();
});

it('если времени не осталось, ничего не предлагает и объясняет почему', function () {
    $this->user->update(['training_settings' => ['time_budget_minutes' => 35]]);
    $workout = makeWorkout($this->user, 'pull', ['barbell-row', 'pull-up']);

    $result = $this->recommender->recommend($workout);

    expect($result->currentMinutes)->toBeGreaterThan(30)
        ->and($result->flat())->toBeEmpty()
        ->and($result->excludedReason('dumbbell-curl'))->toContain('бюджете 35');
});

it('ограничивает число упражнений на мышцу за тренировку', function () {
    $workout = makeWorkout($this->user, 'pull', ['barbell-row', 'pull-up', 'seated-cable-row']);

    $result = $this->recommender->recommend($workout);

    // Спина закрыта тремя упражнениями — дальше только бицепс и предплечья.
    expect(collect($result->flat())->every(fn (Suggestion $s) => ! in_array('back', $s->exercise->primaryMuscles())))->toBeTrue()
        ->and($result->excludedReason('dumbbell-row'))->toContain('Спина');
});

it('не превышает недельный максимум подходов на мышцу', function () {
    $monday = now()->startOfWeek();
    makeWorkout($this->user, 'pull', ['barbell-row', 'lat-pulldown-wide', 'seated-cable-row', 'pull-up', 'dumbbell-row'], $monday->toDateString(), 'completed');

    $workout = makeWorkout($this->user, 'pull', [], $monday->copy()->addDays(2)->toDateString());
    $result = $this->recommender->recommend($workout);

    expect($result->weeklySets['back'])->toEqual(15.0)
        ->and(collect($result->flat())->every(fn (Suggestion $s) => ! in_array('back', $s->exercise->primaryMuscles())))->toBeTrue()
        ->and($result->excludedReason('t-bar-row'))->toContain('Недельный объём');
});

it('без шаблона опирается на мышцы выбранных упражнений', function () {
    $workout = makeWorkout($this->user, null, ['bench-press']);

    $result = $this->recommender->recommend($workout);

    expect($result->dayMuscles)->toBe(['chest'])
        ->and(collect($result->flat())->every(fn (Suggestion $s) => in_array('chest', $s->exercise->primaryMuscles())))->toBeTrue();
});
