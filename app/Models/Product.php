<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    // Atributos que se pueden asignar masivamente
    protected $fillable = [
        'name',
        'description',
        'stock',
        'base_price',
    ];

    // Relaciones
    /**
     * Relación uno a muchos: Un producto puede estar en muchos items de ventas
     */
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }
}
