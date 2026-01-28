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
            $table->string('unit_type')->default('UN'); // UN, FD, CX, KG
            $table->integer('content_quantity')->default(1); // Ex: 12 (se for fardo de 12)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bar_products', function (Blueprint $table) {
            //
        });
    }
};
