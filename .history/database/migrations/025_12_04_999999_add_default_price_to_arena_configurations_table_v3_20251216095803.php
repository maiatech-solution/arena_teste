<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Cria a tabela arena_configurations com todas as colunas necessárias.
     */
    public function up(): void
    {
        Schema::create('arena_configurations', function (Blueprint $table) {
            // 1. Chave Primária
            $table->id();

            // 2. Dia da semana (0-6)
            $table->tinyInteger('day_of_week')
                  ->nullable()
                  ->comment('Dia da semana (0-6)');

            // 3. Dados de configuração em JSON
            $table->json('config_data')
                  ->nullable();

            // 4. Status Ativo (tinyint 1 no banco, equivalente ao boolean)
            $table->boolean('is_active')
                  ->default(true);

            // 5. Preço padrão para reservas
            $table->decimal('default_price', 8, 2)
                  ->default(0.00)
                  ->comment('Preço padrão para reservas avulsas.');

            // 6. Timestamps (created_at e updated_at)
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
