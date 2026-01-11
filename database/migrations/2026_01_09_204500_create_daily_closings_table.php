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
        // Tabla de cierres diarios (cuadre de caja al final del día)
        Schema::create('daily_closings', function (Blueprint $table) {
            $table->id();
            $table->date('closing_date')->unique(); // Fecha del cierre (única, un cierre por día)
            $table->decimal('total_sales', 10, 2)->default(0); // Total de ventas del día
            $table->decimal('total_expenses', 10, 2)->default(0); // Total de gastos del día
            $table->decimal('previous_balance', 10, 2)->default(0); // Saldo del día anterior
            // Fórmula: (total_sales - total_expenses) + previous_balance = final_balance
            $table->decimal('final_balance', 10, 2)->default(0); // Saldo final que pasa al siguiente día
            // Foreign key hacia users - RESTRICT para auditoría (quién hizo el cierre)
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_closings');
    }
};
