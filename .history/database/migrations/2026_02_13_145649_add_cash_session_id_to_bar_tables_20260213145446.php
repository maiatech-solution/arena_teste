<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adiciona na tabela de Pedidos (Mesas)
        Schema::table('bar_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('bar_cash_session_id')->nullable()->after('user_id');
            // Cria o relacionamento oficial com a tabela de sessões
            $table->foreign('bar_cash_session_id')->references('id')->on('bar_cash_sessions')->onDelete('set null');
        });

        // Adiciona na tabela de Vendas (PDV/Direta)
        Schema::table('bar_sales', function (Blueprint $table) {
            $table->unsignedBigInteger('bar_cash_session_id')->nullable()->after('bar_product_id');
            $table->foreign('bar_cash_session_id')->references('id')->on('bar_cash_sessions')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('bar_orders', function (Blueprint $table) {
            $table->dropForeign(['bar_cash_session_id']);
            $table->dropColumn('bar_cash_session_id');
        });

        Schema::table('bar_sales', function (Blueprint $table) {
            $table->dropForeign(['bar_cash_session_id']);
            $table->dropColumn('bar_cash_session_id');
        });
    }
};
