<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            // null — общий каталог, иначе — пользовательское упражнение.
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('slug', 80)->nullable()->unique();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('pattern', 32)->index();
            $table->string('equipment', 16)->index();
            $table->string('mechanic', 16);
            $table->unsignedTinyInteger('fatigue')->default(2);
            $table->string('icon', 32)->nullable();
            $table->unsignedTinyInteger('rep_min')->default(8);
            $table->unsignedTinyInteger('rep_max')->default(12);
            $table->timestamps();
        });

        Schema::create('exercise_muscle', function (Blueprint $table) {
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('muscle_id')->constrained()->cascadeOnDelete();
            $table->string('role', 16); // primary | secondary
            $table->primary(['exercise_id', 'muscle_id']);
            $table->index(['muscle_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_muscle');
        Schema::dropIfExists('exercises');
    }
};
