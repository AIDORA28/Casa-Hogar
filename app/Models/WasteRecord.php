<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WasteRecord extends Model
{
    protected $fillable = [
        'product_id',
        'quantity',
        'reason',
        'waste_date',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'waste_date' => 'date',
            'quantity' => 'integer',
        ];
    }

    /**
     * Relación: Una merma pertenece a un producto
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación: Una merma es registrada por un usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Boot method para descontar stock automáticamente al crear
     */
    protected static function booted()
    {
        static::created(function ($wasteRecord) {
            $product = $wasteRecord->product;
            $product->decrement('stock', $wasteRecord->quantity);
        });

        // Al eliminar, restaurar stock
        static::deleted(function ($wasteRecord) {
            $product = $wasteRecord->product;
            $product->increment('stock', $wasteRecord->quantity);
        });
    }
}
