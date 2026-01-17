<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    // Atributos que se pueden asignar masivamente
    protected $fillable = [
        'user_id',
        'user_name',
        'nurse_id',
        'sale_date',
        'total_amount',
    ];

    // Cast de tipos
    protected $casts = [
        'sale_date' => 'date',
    ];

    // Relaciones
    /**
     * Relación muchos a uno: Una venta pertenece a un usuario (tesorero)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación muchos a uno: Una venta fue entregada por una enfermera
     */
    public function nurse()
    {
        return $this->belongsTo(Nurse::class);
    }

    /**
     * Relación uno a muchos: Una venta tiene muchos items
     */
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }
}
