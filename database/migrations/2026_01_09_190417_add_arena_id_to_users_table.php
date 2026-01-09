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
        Schema::table('users', function (Blueprint $table) {
            // foreignId cria a coluna e o constrained cria a ligação com a tabela arenas
            // nullable() é essencial para que os Clientes (site) não fiquem presos a uma arena
            $table->foreignId('arena_id')->nullable()->after('role')->constrained('arenas')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['arena_id']);
            $table->dropColumn('arena_id');
        });
    }
};
