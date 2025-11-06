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
        // Importante: Tabela correta 'reservas'
        Schema::table('reservas', function (Blueprint $table) {

            // Adiciona a coluna para agrupar reservas fixas (recorrentes)
            // Se a coluna já existir (erro da última execução), ela não é recriada.
            if (!Schema::hasColumn('reservas', 'recurrent_series_id')) {
                $table->unsignedBigInteger('recurrent_series_id')
                      ->nullable();
            }

            // Adiciona campos para identificar reservas fixas (dia da semana e índice)
            if (!Schema::hasColumn('reservas', 'day_of_week')) {
                $table->tinyInteger('day_of_week')->nullable();
            }

            if (!Schema::hasColumn('reservas', 'week_index')) {
                $table->tinyInteger('week_index')->nullable()->comment('1, 2, 3... - ordem da reserva dentro da série');
            }

            // Adiciona a chave estrangeira (FK)
            // Este é o passo que falhou antes e será executado agora com sucesso.
            $table->foreign('recurrent_series_id')
                  ->references('id')
                  ->on('recurrent_series')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            // É sempre seguro tentar remover a chave estrangeira primeiro
            $table->dropForeign(['recurrent_series_id']);

            // Em seguida, remove as colunas, se existirem
            $table->dropColumn(['recurrent_series_id', 'day_of_week', 'week_index']);
        });
    }
};
