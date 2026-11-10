<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DayTemplateController;
use App\Http\Controllers\DishController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\NutritionController;
use App\Http\Controllers\NutritionProfileController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShoppingListController;
use App\Http\Controllers\WeightEntryController;
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

    Route::prefix('nutrition')->name('nutrition.')->group(function () {
        Route::get('/', [NutritionController::class, 'diary'])->name('diary');
        Route::get('/stats', [NutritionController::class, 'stats'])->name('stats');
        Route::get('/foods', [NutritionController::class, 'foods'])->name('foods');

        Route::get('/dishes', [DishController::class, 'index'])->name('dishes.index');
        Route::get('/dishes/{dish}', [DishController::class, 'show'])->name('dishes.show');
        Route::post('/dishes/{dish}/log', [DishController::class, 'log'])->name('dishes.log');

        Route::get('/shopping', [ShoppingListController::class, 'index'])->name('shopping.index');
        Route::post('/shopping', [ShoppingListController::class, 'store'])->name('shopping.store');
        Route::delete('/shopping', [ShoppingListController::class, 'clear'])->name('shopping.clear');
        Route::patch('/shopping/{item}', [ShoppingListController::class, 'update'])->name('shopping.update');
        Route::delete('/shopping/{item}', [ShoppingListController::class, 'destroy'])->name('shopping.destroy');

        Route::post('/weight', [WeightEntryController::class, 'store'])->name('weight.store');
        Route::delete('/weight/{entry}', [WeightEntryController::class, 'destroy'])->name('weight.destroy');

        Route::get('/profile', [NutritionProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [NutritionProfileController::class, 'update'])->name('profile.update');
    });

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::delete('/settings', [SettingsController::class, 'reset'])->name('settings.reset');

    Route::view('/more', 'more')->name('more');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
