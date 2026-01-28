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
        Schema::table('bar_sales', function (Blueprint $table) {
            // Isso transforma a coluna de ENUM para STRING comum,
            // removendo a trava que causa o erro "Data truncated"
            $table->string('payment_method', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('bar_sales', function (Blueprint $table) {
            // Caso precise reverter, ele volta para o padrão antigo
            $table->enum('payment_method', ['dinheiro', 'pix', 'cartao'])->change();
        });
    }
};
