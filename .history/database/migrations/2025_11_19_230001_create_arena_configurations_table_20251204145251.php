<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Use um timestamp mais alto para garantir que seja a próxima migração a rodar
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Verifica se a tabela 'arena_configurations' existe
        if (Schema::hasTable('arena_configurations')) {
            Schema::table('arena_configurations', function (Blueprint $table) {
                // Adiciona a coluna 'default_price' se ela não existir
                if (!Schema::hasColumn('arena_configurations', 'default_price')) {
                    $table->decimal('default_price', 8, 2)
                          ->default(0.00)
                          ->after('is_active') // Adiciona após a coluna 'is_active'
                          ->comment('Preço padrão para reservas avulsas.');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Verifica se a tabela 'arena_configurations' existe
        if (Schema::hasTable('arena_configurations')) {
            Schema::table('arena_configurations', function (Blueprint $table) {
                // Remove a coluna no rollback
                if (Schema::hasColumn('arena_configurations', 'default_price')) {
                    $table->dropColumn('default_price');
                }
            });
        }
    }
};
