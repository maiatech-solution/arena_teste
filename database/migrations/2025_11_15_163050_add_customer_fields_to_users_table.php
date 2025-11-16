<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 🛑 Devemos usar 'Schema::hasColumn' para tornar esta migração robusta.

            // 1. Coluna 'role'
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('gestor')->after('email');
            }

            // 2. Coluna 'whatsapp_contact'
            if (!Schema::hasColumn('users', 'whatsapp_contact')) {
                $table->string('whatsapp_contact', 15)->nullable()->unique()->after('name');
            }

            // 3. Coluna 'data_nascimento'
            if (!Schema::hasColumn('users', 'data_nascimento')) {
                $table->date('data_nascimento')->nullable()->after('whatsapp_contact');
            }
        });

        // Garante que usuários existentes sem role sejam marcados como 'gestor'
        DB::table('users')->whereNull('role')->update(['role' => 'gestor']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'data_nascimento')) {
                $table->dropColumn('data_nascimento');
            }
            if (Schema::hasColumn('users', 'whatsapp_contact')) {
                $table->dropUnique(['whatsapp_contact']);
                $table->dropColumn('whatsapp_contact');
            }
            // Não remove a role
        });
    }
};
