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
        Schema::create('bar_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(); // Quem vendeu
            $table->decimal('total_value', 10, 2);
            $table->enum('payment_method', ['dinheiro', 'pix', 'cartao']);
            $table->enum('status', ['pago', 'cancelado'])->default('pago');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bar_bar_sales');
    }
};
