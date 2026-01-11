<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    // Atributos que se pueden asignar masivamente
    protected $fillable = [
        'user_id',
        'expense_date',
        'amount',
        'description',
    ];

    // Cast de tipos
    protected $casts = [
        'expense_date' => 'date',
    ];

    // Relaciones
    /**
     * Relación muchos a uno: Un gasto pertenece a un usuario (tesorero)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
