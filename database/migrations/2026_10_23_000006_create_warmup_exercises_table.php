<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warmup_exercises', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('name', 120);
            $table->string('type', 16)->index(); // general | mobility
            $table->unsignedTinyInteger('minutes')->default(1);
            $table->string('reps', 20)->nullable();
            $table->json('joints');
            $table->string('description', 255)->nullable();
            $table->string('icon', 32)->nullable();
            $table->timestamps();
        });

        Schema::create('muscle_warmup_exercise', function (Blueprint $table) {
            $table->foreignId('warmup_exercise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('muscle_id')->constrained()->cascadeOnDelete();
            $table->primary(['warmup_exercise_id', 'muscle_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muscle_warmup_exercise');
        Schema::dropIfExists('warmup_exercises');
    }
};
