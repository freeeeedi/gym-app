<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Вес по датам: одна запись в день.
        Schema::create('weight_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('measured_on');
            $table->decimal('weight_kg', 5, 1);
            $table->timestamps();

            $table->unique(['user_id', 'measured_on']);
        });

        // Вода: сумма выпитого за день.
        Schema::create('water_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('logged_on');
            $table->unsignedInteger('ml')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'logged_on']);
        });

        // Блюда, выбранные для списка покупок.
        Schema::create('shopping_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dish_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('servings')->default(1);
            $table->timestamps();

            $table->unique(['user_id', 'dish_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopping_list_items');
        Schema::dropIfExists('water_logs');
        Schema::dropIfExists('weight_entries');
    }
};
