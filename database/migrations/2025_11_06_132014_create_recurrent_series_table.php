<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// O NOME DESTA CLASSE DEVE CORRESPONDER AO NOME DO ARQUIVO
class CreateRecurrentSeriesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Esta tabela armazena metadados para grupos de reservas recorrentes (fixas)
        Schema::create('recurrent_series', function (Blueprint $table) {
            $table->id();

            // Referência ao usuário/cliente que possui esta série de reservas
            // Assumimos que a tabela 'users' já existe.
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained()
                  ->onDelete('set null');

            // Data final da recorrência
            $table->date('end_date')->nullable()->comment('Data de término da série de reservas fixas.');

            // Status da série (se foi cancelada ou está ativa)
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurrent_series');
    }
}
