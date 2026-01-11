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
        // Tabla de ventas (registro de cada venta realizada por un tesorero)
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            // Foreign key hacia users - RESTRICT para mantener auditoría
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('sale_date'); // Fecha de la venta
            $table->decimal('total_amount', 10, 2)->default(0); // Monto total de la venta
            $table->timestamps();
            
            // Índice para búsquedas rápidas por fecha
            $table->index('sale_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
