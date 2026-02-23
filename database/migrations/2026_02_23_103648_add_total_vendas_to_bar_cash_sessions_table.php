<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bar_cash_sessions', function (Blueprint $table) {
            // Adiciona a coluna que o erro apontou
            $table->decimal('total_vendas_sistema', 12, 2)->default(0)->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('bar_cash_sessions', function (Blueprint $table) {
            $table->dropColumn('total_vendas_sistema');
        });
    }
};
