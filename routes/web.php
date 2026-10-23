<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DayTemplateController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WorkoutController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');

    Route::post('/workouts', [WorkoutController::class, 'store'])->name('workouts.store');
    Route::get('/workouts/{workout}', [WorkoutController::class, 'show'])->name('workouts.show');
    Route::patch('/workouts/{workout}', [WorkoutController::class, 'update'])->name('workouts.update');
    Route::delete('/workouts/{workout}', [WorkoutController::class, 'destroy'])->name('workouts.destroy');
    Route::post('/workouts/{workout}/start', [WorkoutController::class, 'start'])->name('workouts.start');
    Route::get('/workouts/{workout}/session', [WorkoutController::class, 'session'])->name('workouts.session');
    Route::post('/workouts/{workout}/finish', [WorkoutController::class, 'finish'])->name('workouts.finish');
    Route::get('/workouts/{workout}/summary', [WorkoutController::class, 'summary'])->name('workouts.summary');

    Route::get('/exercises', [ExerciseController::class, 'index'])->name('exercises.index');
    Route::get('/exercises/create', [ExerciseController::class, 'create'])->name('exercises.create');
    Route::post('/exercises', [ExerciseController::class, 'store'])->name('exercises.store');
    Route::get('/exercises/{exercise}', [ExerciseController::class, 'show'])->name('exercises.show');
    Route::delete('/exercises/{exercise}', [ExerciseController::class, 'destroy'])->name('exercises.destroy');

    Route::resource('templates', DayTemplateController::class)->except('show')
        ->parameters(['templates' => 'template']);

    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::delete('/settings', [SettingsController::class, 'reset'])->name('settings.reset');

    Route::view('/more', 'more')->name('more');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
