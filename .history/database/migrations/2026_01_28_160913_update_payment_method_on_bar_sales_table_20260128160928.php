<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Mudamos para string simples para evitar esse erro no futuro 
        // ou atualizamos o ENUM com as novas opções
        Schema::table('bar_sales', function (Blueprint $table) {
            $table->string('payment_method')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
