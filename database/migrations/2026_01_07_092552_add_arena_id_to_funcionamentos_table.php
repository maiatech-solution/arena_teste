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
        Schema::table('arena_configurations', function (Blueprint $table) {
            // Cria a coluna arena_id que aponta para a tabela arenas
            $table->foreignId('arena_id')->after('id')->constrained('arenas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('funcionamentos', function (Blueprint $table) {
            //
        });
    }
};
