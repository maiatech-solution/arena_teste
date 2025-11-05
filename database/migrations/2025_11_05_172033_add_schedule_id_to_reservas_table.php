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
            // Adiciona a coluna schedule_id como chave estrangeira
            $table->foreignId('schedule_id')
                  ->nullable() // Permite NULL
                  ->after('user_id')
                  ->constrained('schedules') // Associa à sua tabela 'schedules'
                  ->onDelete('set null'); // Se um Schedule for deletado, a reserva fica com schedule_id NULO
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
