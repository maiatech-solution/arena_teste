<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adiciona todas as colunas necessárias para a Reserva (Data, Hora, Cliente, Status).
     */
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();

            // Dados da Reserva (o que foi reservado)
            $table->date('date');           // A data específica da reserva (ex: 2025-11-10)
            $table->time('start_time');     // Hora de início da reserva
            $table->time('end_time');       // Hora de fim da reserva

            // Dados do Cliente (quem reservou)
            $table->string('client_name');
            $table->string('client_contact'); // Contato do cliente (para o WhatsApp)
            //$table->decimal('signal_value', 8, 2);
            $table->text('notes')->nullable(); // Alguma nota adicional

            // Status: 'pending' é o padrão inicial
            $table->enum('status', ['pending', 'confirmed', 'rejected', 'cancelled'])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Remove a tabela se a migração for revertida.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
