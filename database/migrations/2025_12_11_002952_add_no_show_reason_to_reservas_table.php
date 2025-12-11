<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            // Adiciona a coluna para registrar o motivo da falta
            $table->text('no_show_reason')->nullable()->after('cancellation_reason'); 
            
            // Opcional: Adicionar a data/hora da marcação de falta
            // $table->timestamp('no_show_at')->nullable()->after('no_show_reason');
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn('no_show_reason');
            // $table->dropColumn('no_show_at');
        });
    }
};