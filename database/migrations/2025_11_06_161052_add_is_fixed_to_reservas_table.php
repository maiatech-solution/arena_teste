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
        // Verifica se a tabela 'reservas' existe antes de tentar alterá-la.
        if (Schema::hasTable('reservas')) {
            Schema::table('reservas', function (Blueprint $table) {
                // Adiciona a coluna 'is_fixed' como um booleano (true/false).
                // O 'default(false)' garante que as reservas existentes sejam consideradas não fixas (0).
                $table->boolean('is_fixed')->default(false)->after('end_time');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Verifica se a tabela 'reservas' existe e se a coluna 'is_fixed' existe antes de tentar removê-la.
        if (Schema::hasTable('reservas') && Schema::hasColumn('reservas', 'is_fixed')) {
            Schema::table('reservas', function (Blueprint $table) {
                $table->dropColumn('is_fixed');
            });
        }
    }
};
