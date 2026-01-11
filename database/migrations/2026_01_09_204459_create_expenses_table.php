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
        // Tabla de egresos/gastos (registro de compras e insumos)
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            // Foreign key hacia users - RESTRICT para mantener auditoría
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('expense_date'); // Fecha del gasto
            $table->decimal('amount', 10, 2); // Monto del gasto
            $table->text('description'); // Descripción del gasto (insumos, pagos, etc.)
            $table->timestamps();
            
            // Índice para búsquedas rápidas por fecha
            $table->index('expense_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
