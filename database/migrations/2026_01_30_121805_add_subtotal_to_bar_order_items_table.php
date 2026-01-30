<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bar_order_items', function (Blueprint $table) {
            // Cria o campo decimal logo após o unit_price
            $table->decimal('subtotal', 10, 2)->after('unit_price')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('bar_order_items', function (Blueprint $table) {
            $table->dropColumn('subtotal');
        });
    }
};
