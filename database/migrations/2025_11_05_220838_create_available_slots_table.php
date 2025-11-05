<?php
// database/migrations/AAAA_MM_DD_HHMMSS_create_available_slots_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('available_slots', function (Blueprint $table) {
            $table->id();
            // A data exata em que o slot está disponível
            $table->date('date')->index();
            // Hora de início e fim
            $table->time('start_time');
            $table->time('end_time');
            // Preço (pode ser diferente da regra recorrente)
            $table->decimal('price', 8, 2);
            // Para poder desativar um slot avulso sem deletar
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Índice para garantir a busca eficiente por data e horário
            $table->index(['date', 'start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('available_slots');
    }
};
