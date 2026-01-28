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
    Schema::create('bar_sale_items', function (Blueprint $table) {
        $table->id();
        // Aqui garantimos que ele aponte para 'bar_sales'
        $table->foreignId('bar_sale_id')->constrained('bar_sales')->onDelete('cascade');
        $table->foreignId('bar_product_id')->constrained('bar_products');
        $table->integer('quantity');
        $table->decimal('price_at_sale', 10, 2);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bar_bar_sale_items');
    }
};
