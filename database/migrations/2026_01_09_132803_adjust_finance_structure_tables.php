<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // 1. Ajuste na tabela de Transações
        Schema::table('financial_transactions', function (Blueprint $table) {
            // Mudamos para nullable para a transação não sumir se a reserva for deletada
            $table->bigInteger('reserva_id')->unsigned()->nullable()->change();
        });

        // 2. Ajuste na tabela de Caixas (Cashiers)
        Schema::table('cashiers', function (Blueprint $table) {
            // Adicionando campos que faltavam para a auditoria e reabertura
            if (!Schema::hasColumn('cashiers', 'difference')) {
                $table->decimal('difference', 10, 2)->after('actual_amount')->default(0);
            }
            if (!Schema::hasColumn('cashiers', 'reopen_reason')) {
                $table->text('reopen_reason')->nullable()->after('notes');
                $table->timestamp('reopened_at')->nullable()->after('reopen_reason');
                $table->unsignedBigInteger('reopened_by')->nullable()->after('reopened_at');
            }

            // Padronizando o nome da coluna de usuário para Auth::id()
            if (Schema::hasColumn('cashiers', 'closed_by_user_id')) {
                $table->renameColumn('closed_by_user_id', 'user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
