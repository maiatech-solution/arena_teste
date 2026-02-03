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
        Schema::create('bar_cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bar_cash_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained();
            $table->foreignId('bar_order_id')->nullable()->constrained()->onDelete('set null'); // Origem da venda

            $table->enum('type', ['venda', 'reforco', 'sangria', 'estorno']);
            $table->string('payment_method'); // dinheiro, pix, credito, debito, pendurado
            $table->decimal('amount', 10, 2);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bar_cash_movements');
    }
};
