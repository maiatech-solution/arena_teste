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
        Schema::table('bar_products', function (Blueprint $table) {
            // Se você já tinha criado a coluna 'category' como texto, vamos removê-la primeiro
            if (Schema::hasColumn('bar_products', 'category')) {
                $table->dropColumn('category');
            }

            // Adiciona a ligação oficial (chave estrangeira)
            $table->foreignId('bar_category_id')->nullable()->constrained('bar_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
};
