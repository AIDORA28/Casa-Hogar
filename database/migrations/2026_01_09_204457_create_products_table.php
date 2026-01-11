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
        // Tabla de productos (postres) disponibles para vender
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ej: Keke de piña, Chifles, Pan con pollo
            $table->text('description')->nullable();
            $table->integer('stock')->default(0); // Cantidad disponible
            $table->decimal('base_price', 10, 2)->default(0); // Precio base del producto
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
