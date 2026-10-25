<?php

use App\Models\User;
use App\Models\Workout;
use Database\Seeders\DemoSeeder;

it('повторный app:install --demo не стирает данные пользователей', function () {
    $this->artisan('app:install', ['--demo' => true])->assertSuccessful();
    $demo = User::where('email', DemoSeeder::EMAIL)->firstOrFail();
    $own = User::factory()->create();
    makeWorkout($own, 'pull', ['seated-cable-row'], status: 'completed');
    makeWorkout($demo, 'push', ['bench-press'], '2030-01-01');
    $count = Workout::count();

    $this->artisan('app:install', ['--demo' => true])->assertSuccessful();

    expect(Workout::count())->toBe($count)
        ->and(User::where('email', DemoSeeder::EMAIL)->value('id'))->toBe($demo->id);
});

it('app:demo --fresh пересоздаёт демо-пользователя', function () {
    $this->artisan('app:install', ['--demo' => true])->assertSuccessful();
    $id = User::where('email', DemoSeeder::EMAIL)->value('id');

    $this->artisan('app:demo', ['--fresh' => true])->assertSuccessful();

    expect(User::where('email', DemoSeeder::EMAIL)->value('id'))->not->toBe($id);
});
