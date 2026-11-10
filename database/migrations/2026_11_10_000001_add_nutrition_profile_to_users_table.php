<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Профиль питания: пол, возраст, рост, активность, цель, ограничения.
            // Вес хранится отдельно — в weight_entries (нужна история для графика).
            $table->json('nutrition_profile')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('nutrition_profile');
        });
    }
};
