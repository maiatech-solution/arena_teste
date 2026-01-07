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
    Schema::table('financial_transactions', function (Blueprint $table) {
        // Adiciona a coluna arena_id logo após o reserva_id
        $table->unsignedBigInteger('arena_id')->nullable()->after('reserva_id');

        // Opcional: Adicionar a chave estrangeira se a sua tabela de arenas for 'arenas'
        // $table->foreign('arena_id')->references('id')->on('arenas')->onDelete('cascade');
    });
}

public function down()
{
    Schema::table('financial_transactions', function (Blueprint $table) {
        $table->dropColumn('arena_id');
    });
}
};
