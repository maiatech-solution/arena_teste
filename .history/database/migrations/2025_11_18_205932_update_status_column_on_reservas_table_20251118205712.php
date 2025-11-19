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
        // Altera a coluna 'status' para aceitar 20 caracteres
        Schema::table('reservas', function (Blueprint $table) {
            $table->string('status', 20)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverte a alteração, voltando para o VARCHAR(15) se necessário (ou o tamanho anterior)
        // OBS: Deixei 15, mas você pode voltar para o valor anterior se souber qual era.
        Schema::table('reservas', function (Blueprint $table) {
            $table->string('status', 15)->change();
        });
    }
};
