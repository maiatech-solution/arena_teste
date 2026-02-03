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
        Schema::table('bar_cash_sessions', function (Blueprint $table) {
            $table->decimal('expected_balance', 10, 2)->after('opening_balance')->default(0);
            $table->text('notes')->nullable()->after('status'); // Para justificativas de quebra
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bar_cash_sessions', function (Blueprint $table) {
            //
        });
    }
};
