<?php
// database/migrations/YYYY_MM_DD_HHmmss_create_cashiers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashiers', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique(); // Data do fechamento (chave única)
            $table->decimal('calculated_amount', 10, 2); // Valor líquido que o sistema calculou
            $table->decimal('actual_amount', 10, 2);     // Valor que o gestor informou
            $table->string('status')->default('closed'); // 'open', 'closed', 'audited'
            $table->text('notes')->nullable();
            $table->foreignId('closed_by_user_id')->constrained('users'); // Quem fechou
            $table->timestamp('closing_time');           // Quando foi fechado
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashiers');
    }
};