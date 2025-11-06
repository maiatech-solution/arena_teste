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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();

            // Usamos unsignedTinyInteger e a convenção padrão PHP: 0 = Domingo, 6 = Sábado
            $table->unsignedTinyInteger('day_of_week')->comment('0=Sunday, 6=Saturday');

            // Horário de início do expediente (Ex: 09:00:00)
            $table->time('start_time');

            // Horário de fim do expediente (Ex: 18:00:00)
            $table->time('end_time');

            // Preço padrão para um slot de 1 hora.
            $table->decimal('price', 8, 2)->default(0.00);

            // Se esta configuração de horário está ativa ou desativada.
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // CRUCIAL: Garante que não haja configurações duplicadas para o mesmo dia e hora
            $table->unique(['day_of_week', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
