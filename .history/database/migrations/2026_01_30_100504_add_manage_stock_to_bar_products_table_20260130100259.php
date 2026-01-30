<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executa a alteração no banco.
     */
    public function up(): void
    {
        Schema::table('bar_products', function (Blueprint $group) {
            // Criamos a coluna.
            // Default 'true' (1) significa que, por padrão, novos produtos controlam estoque.
            $group->boolean('manage_stock')->default(true)->after('is_active');
        });
    }

    /**
     * Reverte a alteração (caso precise voltar atrás).
     */
    public function down(): void
    {
        Schema::table('bar_products', function (Blueprint $group) {
            $group->dropColumn('manage_stock');
        });
    }
};
