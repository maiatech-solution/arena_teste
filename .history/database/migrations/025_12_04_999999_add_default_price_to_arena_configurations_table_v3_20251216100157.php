<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Cria a tabela arena_configurations idêntica ao banco de dados atual.
     */
    public function up(): void
    {
        Schema::create('arena_configurations', function (Blueprint $table) {
            // id bigint UNSIGNED NOT NULL AUTO_INCREMENT
            $table->id();

            // day_of_week tinyint NULL com comentário
            $table->tinyInteger('day_of_week')
                  ->nullable()
                  ->comment('Dia da semana (0-6)');

            // config_data json NULL
            $table->json('config_data')
                  ->nullable();

            // is_active tinyint(1) NOT NULL DEFAULT 1
            $table->boolean('is_active')
                  ->default(true);

            // default_price decimal(8,2) NOT NULL DEFAULT 0.00 com comentário
            $table->decimal('default_price', 8, 2)
                  ->default(0.00)
                  ->comment('Preço padrão para reservas avulsas.');

            // created_at e updated_at timestamp NULL
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
