<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bar_orders', function (Blueprint $column) {
            $column->string('payment_method')->nullable()->after('status');
            $column->string('customer_name')->nullable()->after('payment_method');
            $column->string('customer_phone')->nullable()->after('customer_name');
            $column->decimal('discount_value', 10, 2)->default(0.00)->after('total_value');
        });
    }

    public function down(): void
    {
        Schema::table('bar_orders', function (Blueprint $column) {
            $column->dropColumn(['payment_method', 'customer_name', 'customer_phone', 'discount_value']);
        });
    }
};
