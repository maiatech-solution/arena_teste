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
        Schema::create('bar_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bar_table_id')->nullable()->constrained('bar_tables');
            $table->foreignId('user_id')->constrained('users'); // Atendente/Gestor que abriu
            $table->decimal('total_value', 10, 2)->default(0);
            $table->enum('status', ['open', 'paid', 'cancelled'])->default('open');
            $table->timestamp('closed_at')->nullable(); // Data/Hora do fechamento da mesa
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bar_orders');
    }
};
