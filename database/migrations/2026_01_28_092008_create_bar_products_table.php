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
        Schema::create('bar_products', function (Blueprint $table) {
            $table->id();
            $table->string('barcode')->unique()->nullable(); // Para o leitor de código de barras
            $table->string('name');
            $table->decimal('purchase_price', 10, 2); // Preço de custo (para o lucro futuro)
            $table->decimal('sale_price', 10, 2);     // Preço de venda
            $table->integer('stock_quantity')->default(0); // Estoque atual
            $table->integer('min_stock')->default(5);      // Alerta de estoque baixo
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bar_products');
    }
};
