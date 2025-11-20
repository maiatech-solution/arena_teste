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
        Schema::create('reservas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('price');
            $table->string('client_name');
            $table->string('client_contact');
            $table->text('notes')->nullable();
            $table->string('status', 20);
            $table->boolean('is_fixed')->default(false);
            $table->tinyInteger('day_of_week')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('schedule_id')->nullable()->index('reservas_schedule_id_foreign');
            $table->string('recurrent_series_id', 36)->nullable()->index('reservas_recurrent_series_id_foreign');
            $table->boolean('is_recurrent')->default(false);
            $table->tinyInteger('week_index')->nullable()->comment('1, 2, 3... - ordem da reserva dentro da série');
            $table->unsignedBigInteger('manager_id')->nullable()->index('reservas_manager_id_foreign');
            $table->text('cancellation_reason')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index('reservas_user_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
