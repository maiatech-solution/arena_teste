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
        Schema::create('schedules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->tinyInteger('day_of_week')->nullable()->comment('0=Domingo, 6=Sábado. Null para slots avulsos.');
            $table->date('date')->nullable()->comment('Data específica para slots avulsos.');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('price');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
