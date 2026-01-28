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
        Schema::create('bar_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bar_product_id')->constrained('bar_products')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users'); // Quem fez a ação
            $table->integer('quantity'); // Ex: +50 ou -5 (saída/perda)
            $table->string('type'); // 'entrada', 'saida', 'venda', 'perda'
            $table->string('description')->nullable(); // Ex: "Nota Fiscal #123" ou "Garrafa quebrada"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bar_bar_stock_movements');
    }
};
