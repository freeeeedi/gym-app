<?php

use App\Livewire\WorkoutPlanner;
use App\Livewire\WorkoutSession;
use App\Models\Exercise;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    seedCatalog();
    $this->user = User::factory()->create();
});

it('полный сценарий: план → подсказки → старт → подходы → завершение → итоги', function () {
    $workout = makeWorkout($this->user, 'pull');
    $row = Exercise::where('slug', 'seated-cable-row')->first();
    $curl = Exercise::where('slug', 'dumbbell-curl')->first();
    $pullup = Exercise::where('slug', 'assisted-pull-up')->first();

    // Пустой день тяги: видны все паттерны и причины.
    $planner = Livewire::actingAs($this->user)->test(WorkoutPlanner::class, ['workout' => $workout])
        ->assertSee('Вертикальная тяга')
        ->assertSee('Горизонтальная тяга')
        ->assertSee('Почему:');

    // Изолирующее, затем базовое — базовое встаёт перед изолирующим.
    $planner->call('add', $curl->id)->call('add', $row->id)
        ->assertSee('Закрывает паттерн «Вертикальная тяга»');
    expect($workout->workoutExercises()->pluck('exercise_id')->all())->toBe([$row->id, $curl->id]);

    $planner->call('add', $pullup->id);
    $planner->call('setSets', $workout->workoutExercises()->where('exercise_id', $curl->id)->value('id'), 4);
    expect($workout->workoutExercises()->where('exercise_id', $curl->id)->value('planned_sets'))->toBe(4);

    $this->actingAs($this->user)->post(route('workouts.start', $workout))->assertRedirect(route('workouts.session', $workout));
    expect($workout->fresh()->status->value)->toBe('in_progress');

    $entryId = $workout->workoutExercises()->where('exercise_id', $row->id)->value('id');
    $session = Livewire::actingAs($this->user)->test(WorkoutSession::class, ['workout' => $workout->fresh()])
        ->call('open', $entryId)
        ->set('weight', '60,5')->set('reps', '10')
        ->call('addSet')
        ->assertHasNoErrors()
        ->assertDispatched('set-logged', seconds: 150)
        ->call('copyLast')
        ->set('weight', '')->call('addSet')->assertHasErrors('weight');

    $sets = $workout->workoutExercises()->find($entryId)->sets;
    expect($sets)->toHaveCount(2)->and($sets->pluck('weight_kg')->all())->toBe([60.5, 60.5]);

    $session->call('updateSet', $sets[1]->id, 'reps', '8')->call('deleteSet', $sets[0]->id);
    expect($workout->workoutExercises()->find($entryId)->sets()->pluck('reps')->all())->toBe([8]);

    $this->actingAs($this->user)->post(route('workouts.finish', $workout))->assertRedirect(route('workouts.summary', $workout));
    $workout->refresh();
    expect($workout->status->value)->toBe('completed')
        ->and($workout->workoutExercises()->count())->toBe(1); // без подходов — убраны

    $this->actingAs($this->user)->get(route('workouts.summary', $workout))
        ->assertOk()->assertSee('Длительность')->assertSee('484'); // 60,5 × 8
});

it('чужой пользователь не может менять план и подходы', function () {
    $workout = makeWorkout($this->user, 'pull', ['seated-cable-row'], status: 'in_progress');
    $other = User::factory()->create();

    Livewire::actingAs($other)->test(WorkoutPlanner::class, ['workout' => $workout])->assertForbidden();
    Livewire::actingAs($other)->test(WorkoutSession::class, ['workout' => $workout])->assertForbidden();
});

it('показывает прошлый результат и подсказку прогрессии при вводе подходов', function () {
    makeWorkout($this->user, 'pull', ['seated-cable-row'], '-4 days', 'completed', weight: 60, reps: 12);
    $workout = makeWorkout($this->user, 'pull', ['seated-cable-row'], status: 'in_progress');

    Livewire::actingAs($this->user)->test(WorkoutSession::class, ['workout' => $workout])
        ->assertSee('Прошлый раз')
        ->assertSee('60×12')
        ->assertSee('повысьте вес до 62,5 кг')
        ->assertSet('weight', '62.5')
        ->assertSet('reps', '8');
});
