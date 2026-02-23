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
        Schema::table('bar_products', function (Blueprint $table) {
            // Criamos uma coluna boleana (sim ou não). 
            // Por padrão (default), todo produto NOVO não é combo (false).
            $table->boolean('is_combo')->default(false)->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bar_products', function (Blueprint $table) {
            $table->dropColumn('is_combo');
        });
    }
};
