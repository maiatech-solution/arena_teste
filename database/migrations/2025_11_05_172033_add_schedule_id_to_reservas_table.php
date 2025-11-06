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
        Schema::table('reservas', function (Blueprint $table) {
            // Removendo o modificador ->after('user_id') para evitar o erro 1054,
            // já que a coluna 'user_id' pode não existir ou não ter sido migrada ainda.
            $table->foreignId('schedule_id')
                  ->nullable()
                  ->constrained('schedules') // Assumindo que a FK é para a tabela 'schedules'
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropForeign(['schedule_id']);
            $table->dropColumn('schedule_id');
        });
    }
};
