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
        Schema::create('available_slots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('date')->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('price');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['date', 'start_time', 'end_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('available_slots');
    }
};
