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
    Schema::table('bar_products', function (Blueprint $table) {
        // Criando a coluna de categoria com um valor padrão
        $table->string('category')->default('Outros')->after('name');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bar_products', function (Blueprint $table) {
            //
        });
    }
};
