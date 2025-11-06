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
        // O método ->change() exige que você redefina o tipo da coluna.
        // Tornamos explicitamente 'day_of_week' nulo, resolvendo o erro 1048.
        Schema::table('schedules', function (Blueprint $table) {
            $table->tinyInteger('day_of_week')
                  ->nullable()
                  ->comment('0=Domingo, 6=Sábado. Null para slots avulsos.')
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverte a coluna para NOT NULL, se necessário.
        Schema::table('schedules', function (Blueprint $table) {
            $table->tinyInteger('day_of_week')
                  ->nullable(false)
                  ->comment('0=Domingo, 6=Sábado. Null para slots avulsos.')
                  ->change();
        });
    }
};
