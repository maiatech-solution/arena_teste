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
        // Adicionamos 'inactive' à lista de permitidos
        DB::statement("ALTER TABLE bar_tables MODIFY COLUMN status ENUM('available', 'occupied', 'reserved', 'inactive') DEFAULT 'available'");
    }

    public function down()
    {
        // Remove o 'inactive' caso precise voltar atrás
        DB::statement("ALTER TABLE bar_tables MODIFY COLUMN status ENUM('available', 'occupied', 'reserved') DEFAULT 'available'");
    }
};
