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
        Schema::table('schedules', function (Blueprint $table) {

            // 1. Garante a coluna day_of_week (Recorrente)
            if (!Schema::hasColumn('schedules', 'day_of_week')) {
                $table->tinyInteger('day_of_week')
                      ->nullable()
                      ->after('id')
                      ->comment('0=Domingo, 6=Sábado. Usado para horários recorrentes.');
            }

            // 2. Garante a coluna date (Avulso)
            if (!Schema::hasColumn('schedules', 'date')) {
                // A coluna 'date' estava faltando, causando o erro de QueryException.
                $table->date('date')
                      ->nullable()
                      ->after('day_of_week')
                      ->comment('Data específica para slots avulsos.');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            // Remove apenas se existir, para evitar falhas no rollback
            if (Schema::hasColumn('schedules', 'date')) {
                $table->dropColumn('date');
            }
            if (Schema::hasColumn('schedules', 'day_of_week')) {
                $table->dropColumn('day_of_week');
            }
        });
    }
};
