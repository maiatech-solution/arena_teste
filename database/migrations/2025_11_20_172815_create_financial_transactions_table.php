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
        Schema::create('financial_transactions', function (Blueprint $table) {
        $table->id();
        
        // Vínculos
        $table->foreignId('reserva_id')->constrained('reservas')->onDelete('cascade');
        $table->unsignedBigInteger('user_id')->nullable(); // Cliente que pagou
        $table->unsignedBigInteger('manager_id')->nullable(); // Gestor que registrou (Caixa)
        
        // Dados Financeiros
        $table->decimal('amount', 10, 2); // Valor da transação
        
        // Tipo de Pagamento: 'signal' (sinal), 'remaining' (restante), 'full' (total), 'refund' (estorno)
        $table->string('type', 20); 
        
        // Método: 'pix', 'money', 'card', 'debit'
        $table->string('payment_method', 20); 
        
        // Descrição opcional (ex: "Pix comprovante XYZ")
        $table->string('description')->nullable();
        
        // Data exata do pagamento (CRUCIAL para o relatório diário "Caixa do Dia")
        $table->timestamp('paid_at')->useCurrent();
        
        $table->timestamps();
        
        // Foreign Keys manuais para usuários (pois podem ser nulos)
        $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
