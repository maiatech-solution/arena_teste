<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 🎯 O nome da classe deve corresponder exatamente ao nome extraído do nome do arquivo
class AddNotesColumnToCashiersTable extends Migration 
{
    public function up(): void
    {
        Schema::table('cashiers', function (Blueprint $table) {
            // Adicionar o campo 'notes' para logs de auditoria/justificativas de reabertura
            $table->text('notes')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('cashiers', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
}