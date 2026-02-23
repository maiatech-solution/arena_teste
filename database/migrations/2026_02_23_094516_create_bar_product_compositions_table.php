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
        // 🛡️ Se a tabela já existir de uma tentativa falha anterior, apaga ela para criar do zero
        Schema::dropIfExists('bar_product_compositions');

        Schema::create('bar_product_compositions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('bar_products')->onDelete('cascade');
            $table->foreignId('child_id')->constrained('bar_products')->onDelete('cascade');
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bar_product_compositions');
    }
};
