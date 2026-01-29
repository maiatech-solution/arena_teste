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
        Schema::table('company_infos', function (Blueprint $table) {
            // 0: Nenhum (pendente), 1: Arena, 2: Bar, 3: Ambos
            $table->integer('modules_active')->default(0)->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_infos', function (Blueprint $table) {
            //
        });
    }
};
