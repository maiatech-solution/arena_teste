<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('arena_configurations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->tinyInteger('day_of_week')->nullable()->unique('day_of_week')->comment('Dia da semana (0-6)');
            $table->timestamps();
            $table->json('config_data')->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arena_configurations');
    }
};
