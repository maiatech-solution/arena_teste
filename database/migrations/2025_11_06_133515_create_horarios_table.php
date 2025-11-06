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
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();

            // Campo para Horários Recorrentes (0=Dom a 6=Sáb). Null para slots Avulsos.
            $table->tinyInteger('day_of_week')->nullable()->comment('0=Domingo, 6=Sábado. Usado para horários recorrentes.');

            // Campo para Horários Avulsos (Data Específica). Null para grade Recorrente.
            $table->date('date')->nullable()->comment('Data específica para slots avulsos.');

            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('price', 8, 2);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Índice para garantir que a combinação de dia/data e horário seja única
            // Opcional, mas recomendado:
            // $table->unique(['day_of_week', 'start_time', 'end_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};
