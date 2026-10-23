<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('day_templates', function (Blueprint $table) {
            $table->id();
            // null — стандартный шаблон, иначе — шаблон пользователя.
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('slug', 40)->nullable()->unique();
            $table->string('name', 80);
            $table->string('description', 255)->nullable();
            // Обязательные паттерны движения (slug-и из config('training.patterns')).
            $table->json('patterns');
            $table->timestamps();
        });

        Schema::create('day_template_muscle', function (Blueprint $table) {
            $table->foreignId('day_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('muscle_id')->constrained()->cascadeOnDelete();
            $table->primary(['day_template_id', 'muscle_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('day_template_muscle');
        Schema::dropIfExists('day_templates');
    }
};
