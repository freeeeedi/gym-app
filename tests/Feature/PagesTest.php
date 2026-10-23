<?php

use App\Models\DayTemplate;
use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use Database\Seeders\DemoSeeder;

beforeEach(function () {
    seedCatalog();
    $this->seed(DemoSeeder::class);
    $this->user = User::where('email', DemoSeeder::EMAIL)->first();
});

it('открывает все основные страницы демо-пользователя без ошибок', function () {
    $workout = $this->user->workouts()->where('status', 'completed')->latest('scheduled_on')->first();
    $planned = $this->user->workouts()->where('status', 'planned')->first();
    $bench = Exercise::where('slug', 'bench-press')->first();

    $urls = [
        route('dashboard'),
        route('calendar'),
        route('calendar', ['view' => 'week']),
        route('workouts.show', $planned),
        route('workouts.show', $workout),
        route('workouts.summary', $workout),
        route('workouts.session', $workout),
        route('exercises.index'),
        route('exercises.create'),
        route('exercises.show', $bench),
        route('templates.index'),
        route('templates.create'),
        route('analytics'),
        route('settings.edit'),
        route('more'),
        route('profile.edit'),
    ];

    foreach ($urls as $url) {
        $this->actingAs($this->user)->get($url)->assertOk();
    }
});

it('показывает стагнацию жима и графики на демо-данных', function () {
    $bench = Exercise::where('slug', 'bench-press')->first();

    $this->actingAs($this->user)->get(route('exercises.show', $bench))
        ->assertOk()
        ->assertSee('chart-weight')
        ->assertSee('Нет роста')
        ->assertSee('Формула Эпли');
});

it('гость перенаправляется на вход', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('пользователь не видит чужие тренировки', function () {
    $other = User::factory()->create();
    $workout = $this->user->workouts()->first();

    $this->actingAs($other)->get(route('workouts.show', $workout))->assertForbidden();
    $this->actingAs($other)->get(route('workouts.session', $workout))->assertForbidden();
    $this->actingAs($other)->delete(route('workouts.destroy', $workout))->assertForbidden();
    $this->actingAs($other)->patch(route('workouts.update', $workout), ['title' => 'x'])->assertForbidden();
});

it('создаёт тренировку на дату, переносит, меняет статус и удаляет', function () {
    $template = DayTemplate::where('slug', 'push')->first();

    $this->actingAs($this->user)->post(route('workouts.store'), [
        'scheduled_on' => '2030-01-15', 'day_template_id' => $template->id,
    ])->assertRedirect();
    $workout = Workout::where('scheduled_on', '2030-01-15')->firstOrFail();
    expect($workout->title)->toBe($template->name)->and($workout->user_id)->toBe($this->user->id);

    $this->actingAs($this->user)->patch(route('workouts.update', $workout), ['scheduled_on' => '2030-01-17'])->assertRedirect();
    expect($workout->fresh()->scheduled_on->toDateString())->toBe('2030-01-17');

    $this->actingAs($this->user)->patch(route('workouts.update', $workout), ['status' => 'in_progress']);
    expect($workout->fresh()->status->value)->toBe('in_progress')->and($workout->fresh()->started_at)->not->toBeNull();

    $this->actingAs($this->user)->delete(route('workouts.destroy', $workout))->assertRedirect();
    expect(Workout::find($workout->id))->toBeNull();
});

it('не даёт выбрать чужой шаблон', function () {
    $foreign = User::factory()->create()->dayTemplates()->create(['name' => 'Чужой', 'patterns' => []]);

    $this->actingAs($this->user)->post(route('workouts.store'), [
        'scheduled_on' => '2030-01-15', 'day_template_id' => $foreign->id,
    ])->assertSessionHasErrors('day_template_id');
});

it('создаёт свой шаблон и своё упражнение', function () {
    $this->actingAs($this->user)->post(route('templates.store'), [
        'name' => 'Руки', 'muscles' => ['biceps', 'triceps'], 'patterns' => ['elbow_flexion', 'elbow_extension'],
    ])->assertRedirect(route('templates.index'));
    $template = DayTemplate::where('name', 'Руки')->first();
    expect($template->user_id)->toBe($this->user->id);

    $this->actingAs($this->user)->post(route('exercises.store'), [
        'name' => 'Сгибания с резинкой', 'pattern' => 'elbow_flexion', 'equipment' => 'bodyweight',
        'mechanic' => 'isolation', 'fatigue' => 1, 'rep_min' => 12, 'rep_max' => 20,
        'primary' => ['biceps'], 'icon' => 'curl',
    ])->assertRedirect();
    $exercise = Exercise::where('name', 'Сгибания с резинкой')->first();
    expect($exercise->user_id)->toBe($this->user->id);

    // Своё упражнение видно только владельцу.
    $this->actingAs(User::factory()->create())->get(route('exercises.show', $exercise))->assertForbidden();
});

it('сохраняет настройки и валидирует их', function () {
    $this->actingAs($this->user)->put(route('settings.update'), [
        ...config('training.defaults'), 'time_budget_minutes' => 75, 'rotation_fallback' => '0',
    ])->assertRedirect();
    expect($this->user->fresh()->trainingSettings()->int('time_budget_minutes'))->toBe(75);

    $this->actingAs($this->user)->put(route('settings.update'), [
        ...config('training.defaults'), 'weekly_sets_min' => 20, 'weekly_sets_max' => 10,
    ])->assertSessionHasErrors('weekly_sets_max');
});
