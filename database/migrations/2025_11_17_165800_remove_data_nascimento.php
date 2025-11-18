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
        Schema::table('users', function (Blueprint $table) {
            // Certifique-se de que o nome da coluna aqui corresponde ao nome real
            // Se a sua coluna for 'data_nascimento', use $table->dropColumn('data_nascimento');
            $table->dropColumn('data_nascimento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Adicione a coluna de volta se precisar reverter (importante!)
            $table->date('data_nascimento')->nullable();
        });
    }
};
