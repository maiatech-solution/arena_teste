<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cashiers', function (Blueprint $table) {
            // Mudando a coluna para aceitar nulo
            $table->timestamp('closing_time')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('cashiers', function (Blueprint $table) {
            // Caso precise reverter, ela volta a ser obrigatória
            $table->timestamp('closing_time')->nullable(false)->change();
        });
    }
};
