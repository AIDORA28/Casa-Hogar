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
        // Tabla de items de venta (detalle de productos vendidos en cada venta)
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            // Foreign key a sales - CASCADE para eliminar items si se elimina la venta
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            // Foreign key a products - RESTRICT para mantener histórico
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->integer('quantity'); // Cantidad vendida
            $table->decimal('unit_price', 10, 2); // Precio unitario al momento de la venta
            $table->decimal('subtotal', 10, 2); // quantity * unit_price
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
