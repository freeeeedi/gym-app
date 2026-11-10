<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foods', function (Blueprint $table) {
            $table->id();
            // null — общий каталог, иначе — продукт пользователя.
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('slug', 80)->nullable()->unique();
            $table->string('name', 120);
            $table->string('category', 16)->index();
            // КБЖУ на 100 г.
            $table->decimal('kcal', 6, 1);
            $table->decimal('protein', 5, 1);
            $table->decimal('fat', 5, 1);
            $table->decimal('carbs', 5, 1);
            // Признаки «содержит»: meat, fish, dairy, lactose, eggs, gluten, nuts, occasional.
            $table->json('tags')->nullable();
            // Типичная порция для быстрого ввода: «1 шт.» = 55 г.
            $table->decimal('serving_g', 6, 1)->nullable();
            $table->string('serving_name', 40)->nullable();
            $table->timestamps();
        });

        Schema::create('favorite_foods', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('food_id')->constrained('foods')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->primary(['user_id', 'food_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_foods');
        Schema::dropIfExists('foods');
    }
};
