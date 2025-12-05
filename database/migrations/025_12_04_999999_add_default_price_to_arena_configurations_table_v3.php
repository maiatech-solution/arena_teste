<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations (Cria a tabela com todas as colunas).
     *
     * @return void
     */
    public function up(): void
    {
        // Se a tabela já existir, não a recriamos, apenas garantimos a coluna.
        // Mas se a tabela não existe, nós a criamos com a estrutura COMPLETA:
        if (!Schema::hasTable('arena_configurations')) {
            Schema::create('arena_configurations', function (Blueprint $table) {
                // Colunas originais da migration de criação:
                $table->bigIncrements('id');
                $table->tinyInteger('day_of_week')->nullable()->unique('day_of_week')->comment('Dia da semana (0-6)');
                $table->json('config_data')->nullable();
                $table->boolean('is_active')->default(true);
                
                // Coluna adicionada da migration de alteração:
                $table->decimal('default_price', 8, 2)
                      ->default(0.00)
                      // A coluna 'is_active' já foi definida, então a ordem está OK.
                      ->comment('Preço padrão para reservas avulsas.'); 
                      
                $table->timestamps();
            });
        } 
        
        // Se a tabela JÁ existia no seu banco, mas não tinha a coluna (caso de banco de desenvolvimento antigo)
        // Isso garante que a coluna seja adicionada mesmo se a migration de criação já rodou.
        else {
             Schema::table('arena_configurations', function (Blueprint $table) {
                if (!Schema::hasColumn('arena_configurations', 'default_price')) {
                    $table->decimal('default_price', 8, 2)->default(0.00)->after('is_active')->comment('Preço padrão para reservas avulsas.');
                }
            });
        }
    }

    /**
     * Reverse the migrations (Remove a tabela).
     *
     * @return void
     */
    public function down(): void
    {
        // No rollback, a ação é sempre remover a tabela por completo.
        Schema::dropIfExists('arena_configurations');
    }
};