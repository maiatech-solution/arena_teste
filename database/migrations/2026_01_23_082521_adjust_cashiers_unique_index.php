<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashiers', function (Blueprint $table) {
            // 1. Removemos o índice único que está travando a data
            // O nome padrão criado pelo Laravel é 'tabela_coluna_unique'
            $table->dropUnique('cashiers_date_unique');

            // 2. Criamos a nova chave única composta
            // Agora o banco permite a mesma data, desde que a arena_id seja diferente
            $table->unique(['date', 'arena_id'], 'cashiers_date_arena_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cashiers', function (Blueprint $table) {
            // Reverte o processo caso precise dar rollback
            $table->dropUnique('cashiers_date_arena_unique');
            $table->unique('date');
        });
    }
};
