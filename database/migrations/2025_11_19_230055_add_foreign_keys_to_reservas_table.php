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
        Schema::table('reservas', function (Blueprint $table) {
            $table->foreign(['manager_id'])->references(['id'])->on('users')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['schedule_id'])->references(['id'])->on('schedules')->onUpdate('restrict')->onDelete('set null');
            $table->foreign(['user_id'])->references(['id'])->on('users')->onUpdate('restrict')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropForeign('reservas_manager_id_foreign');
            $table->dropForeign('reservas_schedule_id_foreign');
            $table->dropForeign('reservas_user_id_foreign');
        });
    }
};
