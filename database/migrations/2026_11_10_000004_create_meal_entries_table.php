<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Дневник питания. КБЖУ записи — снимок на момент ввода (за всю съеденную порцию):
        // правка или удаление продукта в каталоге не меняет историю.
        Schema::create('meal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('eaten_on');
            $table->string('meal', 16); // breakfast | lunch | dinner | snack
            $table->foreignId('food_id')->nullable()->constrained('foods')->nullOnDelete();
            $table->foreignId('dish_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            // Граммы — для продуктов; порции — для блюд; оба пустые — ручной ввод «за порцию».
            $table->decimal('grams', 7, 1)->nullable();
            $table->decimal('servings', 4, 2)->nullable();
            $table->decimal('kcal', 7, 1);
            $table->decimal('protein', 6, 1);
            $table->decimal('fat', 6, 1);
            $table->decimal('carbs', 6, 1);
            $table->timestamps();

            $table->index(['user_id', 'eaten_on']);
            $table->index(['user_id', 'food_id']);
        });

        // Шаблоны приёмов пищи: сохранённый набор записей, добавляется одним нажатием.
        Schema::create('meal_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->timestamps();
        });

        Schema::create('meal_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('food_id')->nullable()->constrained('foods')->nullOnDelete();
            $table->foreignId('dish_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->decimal('grams', 7, 1)->nullable();
            $table->decimal('servings', 4, 2)->nullable();
            $table->decimal('kcal', 7, 1);
            $table->decimal('protein', 6, 1);
            $table->decimal('fat', 6, 1);
            $table->decimal('carbs', 6, 1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_template_items');
        Schema::dropIfExists('meal_templates');
        Schema::dropIfExists('meal_entries');
    }
};
