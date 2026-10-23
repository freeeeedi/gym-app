<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('day_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 120);
            $table->date('scheduled_on');
            $table->string('status', 16)->default('planned'); // planned | in_progress | completed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'scheduled_on']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('workout_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->unsignedTinyInteger('planned_sets')->default(3);
            $table->timestamps();

            $table->unique(['workout_id', 'exercise_id']);
            $table->index('exercise_id');
        });

        Schema::create('workout_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->decimal('weight_kg', 6, 2)->default(0);
            $table->unsignedSmallInteger('reps');
            $table->boolean('is_warmup')->default(false);
            $table->timestamps();

            $table->index(['workout_exercise_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_sets');
        Schema::dropIfExists('workout_exercises');
        Schema::dropIfExists('workouts');
    }
};
