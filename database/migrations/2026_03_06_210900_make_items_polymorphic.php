<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ajustar Itens de Venda Direta (PDV)
        Schema::table('bar_sale_items', function (Blueprint $table) {
            // Tornamos o bar_product_id antigo opcional para não quebrar o histórico
            $table->unsignedBigInteger('bar_product_id')->nullable()->change();
            
            // Adicionamos as colunas polimórficas
            $table->unsignedBigInteger('itemable_id')->nullable()->after('bar_sale_id');
            $table->string('itemable_type')->nullable()->after('itemable_id');
            
            $table->index(['itemable_id', 'itemable_type']);
        });

        // 2. Ajustar Itens de Mesas/Comandas
        Schema::table('bar_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('bar_product_id')->nullable()->change();
            
            $table->unsignedBigInteger('itemable_id')->nullable()->after('bar_order_id');
            $table->string('itemable_type')->nullable()->after('itemable_id');
            
            $table->index(['itemable_id', 'itemable_type']);
        });
    }

    public function down(): void
    {
        Schema::table('bar_order_items', function (Blueprint $table) {
            $table->dropColumn(['itemable_id', 'itemable_type']);
        });

        Schema::table('bar_sale_items', function (Blueprint $table) {
            $table->dropColumn(['itemable_id', 'itemable_type']);
        });
    }
};