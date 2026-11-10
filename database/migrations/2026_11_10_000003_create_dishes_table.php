<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dishes', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 120);
            // Для каких приёмов пищи подходит: breakfast, lunch, dinner, snack.
            $table->json('meals');
            // На сколько порций рассчитан рецепт (граммовки ингредиентов — на весь рецепт).
            $table->unsignedTinyInteger('servings')->default(1);
            $table->unsignedSmallInteger('cook_minutes')->nullable();
            $table->text('recipe')->nullable();
            $table->timestamps();
        });

        Schema::create('dish_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dish_id')->constrained()->cascadeOnDelete();
            $table->foreignId('food_id')->constrained('foods')->restrictOnDelete();
            $table->decimal('grams', 7, 1);
            $table->unsignedSmallInteger('position')->default(0);

            $table->unique(['dish_id', 'food_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dish_ingredients');
        Schema::dropIfExists('dishes');
    }
};
