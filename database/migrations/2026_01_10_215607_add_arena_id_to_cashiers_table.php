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
        Schema::table('cashiers', function (Blueprint $table) {
            // Criamos a coluna arena_id. 
            // Ela é nullable para não quebrar fechamentos antigos que não tinham arena.
            $table->foreignId('arena_id')
                ->nullable()
                ->after('id') // Coloca logo após o ID
                ->constrained('arenas')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('cashiers', function (Blueprint $table) {
            $table->dropForeign(['arena_id']);
            $table->dropColumn('arena_id');
        });
    }
};
