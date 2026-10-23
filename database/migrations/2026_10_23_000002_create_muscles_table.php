<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muscles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 32)->unique();
            $table->string('name', 64);
            $table->unsignedSmallInteger('sort')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muscles');
    }
};
