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
    Schema::create('company_infos', function (Blueprint $table) {
        $table->id();
        $table->string('nome_fantasia'); // Elite Soccer
        $table->string('cnpj')->nullable();
        $table->string('whatsapp_suporte')->nullable();
        $table->string('email_contato')->nullable();
        $table->string('cep', 9)->nullable();
        $table->string('logradouro')->nullable();
        $table->string('numero', 20)->nullable();
        $table->string('bairro')->nullable();
        $table->string('cidade')->nullable();
        $table->string('estado', 2)->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_infos');
    }
};
