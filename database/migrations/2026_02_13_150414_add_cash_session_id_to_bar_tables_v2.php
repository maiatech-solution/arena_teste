<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajuste na tabela de Pedidos (Mesas)
        if (Schema::hasTable('bar_orders')) {
            Schema::table('bar_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('bar_orders', 'bar_cash_session_id')) {
                    $table->unsignedBigInteger('bar_cash_session_id')->nullable()->after('user_id');
                    $table->foreign('bar_cash_session_id')->references('id')->on('bar_cash_sessions')->onDelete('set null');
                }
            });
        }

        // Ajuste na tabela de Vendas (PDV)
        if (Schema::hasTable('bar_sales')) {
            Schema::table('bar_sales', function (Blueprint $table) {
                if (!Schema::hasColumn('bar_sales', 'bar_cash_session_id')) {
                    $table->unsignedBigInteger('bar_cash_session_id')->nullable()->after('user_id');
                    $table->foreign('bar_cash_session_id')->references('id')->on('bar_cash_sessions')->onDelete('set null');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bar_orders')) {
            Schema::table('bar_orders', function (Blueprint $table) {
                $table->dropForeign(['bar_cash_session_id']);
                $table->dropColumn('bar_cash_session_id');
            });
        }

        if (Schema::hasTable('bar_sales')) {
            Schema::table('bar_sales', function (Blueprint $table) {
                $table->dropForeign(['bar_cash_session_id']);
                $table->dropColumn('bar_cash_session_id');
            });
        }
    }
};
