<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    // Atributos que se pueden asignar masivamente
    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    // Relaciones
    /**
     * Relación muchos a uno: Un item de venta pertenece a una venta
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Relación muchos a uno: Un item de venta corresponde a un producto
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
