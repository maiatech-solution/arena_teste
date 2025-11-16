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
            $table->id();
            // 0=Domingo, 1=Segunda... 6=Sábado. Garante que só há uma configuração por dia.
            $table->tinyInteger('day_of_week')->unique()->comment('Dia da semana (0-6)');
            $table->time('start_time')->comment('Hora de início do funcionamento');
            $table->time('end_time')->comment('Hora de fim do funcionamento');
            $table->decimal('default_price', 8, 2)->default(0.00)->comment('Preço padrão da hora');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
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
