<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            // 1. Remove a regra de "cascade" antiga
            // O Laravel identifica a chave pelo nome da coluna em um array
            $table->dropForeign(['reserva_id']);

            // 2. Aplica a nova regra "set null"
            // Isso garante que se a reserva sumir, a transação fica com reserva_id = null
            $table->foreign('reserva_id')
                  ->references('id')
                  ->on('reservas')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropForeign(['reserva_id']);
            $table->foreign('reserva_id')
                  ->references('id')
                  ->on('reservas')
                  ->onDelete('cascade');
        });
    }
};