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
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('whatsapp_contact', 15)->nullable()->unique();
            $table->string('email')->unique();
            $table->string('role', 20)->default('cliente');            
            
            $table->boolean('is_vip')->default(false); // Bom pagador (Desconto/Sem sinal)
            $table->boolean('is_blocked')->default(false); // Blacklist (Bloqueia novos agendamentos)
            $table->integer('no_show_count')->default(0); // Contador de faltas

            //$table->decimal('custom_discount_rate', 5, 2)->default(0); // Desconto fixo em % (opcional)
            $table->string('customer_qualification', 20)->default('normal');

            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
